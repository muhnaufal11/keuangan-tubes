<?php

namespace App\GraphQL\Mutations;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Support\Broker;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use GraphQL\Error\Error;

class CreateTransaction
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $userId = $context->request()->attributes->get('user_id');
        $token = $context->request()->bearerToken();
        $ref = (string) Str::uuid();

        $type = $args['type'];
        $rekeningId = $args['rekening_id'];
        $kategori = $args['kategori'];
        $amount = $args['amount'];
        $deskripsi = $args['deskripsi'] ?? null;
        $tanggal = $args['tanggal'];

        if (!in_array($type, ['income', 'expense'])) {
            throw new Error("Invalid transaction type. Allowed: income, expense.");
        }

        // 1. Panggil account-service untuk adjust-balance
        $direction = ($type === 'income') ? 'credit' : 'debit';
        $accountServiceUrl = rtrim(env('ACCOUNT_SERVICE_URL', 'http://account-service:8000'), '/');

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->post($accountServiceUrl . "/api/internal/accounts/{$rekeningId}/adjust-balance", [
                    'amount' => $amount,
                    'direction' => $direction,
                    'ref' => $ref
                ]);
        } catch (\Throwable $e) {
            throw new Error('Gagal menghubungi account-service: ' . $e->getMessage());
        }

        if (!$resp->successful()) {
            $errData = $resp->json();
            $msg = $errData['message'] ?? 'Terjadi kesalahan pada account-service.';
            throw new Error($msg);
        }

        // 2. Tulis baris ledger di local DB
        $transaksi = null;
        DB::beginTransaction();
        try {
            if ($type === 'income') {
                $transaksi = Pemasukan::create([
                    'user_id' => $userId,
                    'rekening_id' => $rekeningId,
                    'kategori' => $kategori,
                    'deskripsi' => $deskripsi,
                    'jumlah' => $amount,
                    'tanggal' => $tanggal
                ]);
                $transaksi->jenis = 'pemasukan';
            } else {
                $transaksi = Pengeluaran::create([
                    'user_id' => $userId,
                    'rekening_id' => $rekeningId,
                    'kategori' => $kategori,
                    'deskripsi' => $deskripsi,
                    'jumlah' => $amount,
                    'tanggal' => $tanggal
                ]);
                $transaksi->jenis = 'pengeluaran';
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // 3. KOMPENSASI: panggil adjust-balance arah kebalikan (ref baru) untuk balikin saldo
            $compRef = (string) Str::uuid();
            $compDirection = ($type === 'income') ? 'debit' : 'credit';
            try {
                Http::withToken($token)
                    ->acceptJson()
                    ->timeout(10)
                    ->post($accountServiceUrl . "/api/internal/accounts/{$rekeningId}/adjust-balance", [
                        'amount' => $amount,
                        'direction' => $compDirection,
                        'ref' => $compRef
                    ]);
            } catch (\Throwable $compEx) {
                \Log::critical("GraphQL Kompensasi saldo gagal: " . $compEx->getMessage(), [
                    'user_id' => $userId,
                    'rekening_id' => $rekeningId,
                    'amount' => $amount,
                    'direction' => $compDirection
                ]);
            }

            throw new Error('Gagal menyimpan transaksi ke database lokal. Saldo telah dikompensasi. Error: ' . $e->getMessage());
        }

        // 4. Publish event transaction.created ke Redis
        Broker::publish([
            'event' => 'transaction.created',
            'user_id' => $userId,
            'type' => $type,
            'rekening_id' => (int) $rekeningId,
            'amount' => (float) $amount,
            'kategori' => $kategori,
            'deskripsi' => $deskripsi,
            'transaction_id' => $transaksi->id,
            'tanggal' => $tanggal
        ]);

        return $transaksi;
    }
}
