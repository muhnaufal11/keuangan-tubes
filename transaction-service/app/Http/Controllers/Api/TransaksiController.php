<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Support\Broker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    /**
     * Menampilkan daftar semua transaksi (pemasukan & pengeluaran digabung).
     */
    public function index(Request $request)
    {
        $userId = $request->attributes->get('user_id');

        $pemasukan = Pemasukan::where('user_id', $userId)
            ->when($request->start_date, fn($q) => $q->where('tanggal', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('tanggal', '<=', $request->end_date))
            ->get()
            ->map(function($item) {
                $item->jenis = 'pemasukan';
                return $item;
            });

        $pengeluaran = Pengeluaran::where('user_id', $userId)
            ->when($request->start_date, fn($q) => $q->where('tanggal', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('tanggal', '<=', $request->end_date))
            ->get()
            ->map(function($item) {
                $item->jenis = 'pengeluaran';
                return $item;
            });

        // concat (bukan merge): merge pada Eloquent Collection men-dedup berdasarkan primary key,
        // sehingga pemasukan id=1 ketimpa pengeluaran id=1. concat menggabungkan semua baris.
        $merged = $pemasukan->concat($pengeluaran)->sortByDesc('tanggal')->values();

        return response()->json($merged);
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'rekening_id' => 'required|integer',
            'kategori' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date'
        ]);

        $userId = $request->attributes->get('user_id');
        $token = $request->bearerToken();
        $ref = (string) Str::uuid();

        // 1. Panggil account-service untuk adjust-balance
        $direction = ($request->type === 'income') ? 'credit' : 'debit';
        $accountServiceUrl = rtrim(env('ACCOUNT_SERVICE_URL', 'http://account-service:8000'), '/');

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->post($accountServiceUrl . "/api/internal/accounts/{$request->rekening_id}/adjust-balance", [
                    'amount' => $request->amount,
                    'direction' => $direction,
                    'ref' => $ref
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghubungi account-service: ' . $e->getMessage()
            ], 502);
        }

        // Jika saldo kurang (409) atau rekening tidak ketemu (404), return error langsung, jangan tulis ledger.
        if (!$resp->successful()) {
            return response()->json(
                $resp->json() ?? ['message' => 'Terjadi kesalahan pada account-service.'],
                $resp->status()
            );
        }

        // 2. Tulis baris ledger di transaction_db
        $transaksi = null;
        DB::beginTransaction();
        try {
            if ($request->type === 'income') {
                $transaksi = Pemasukan::create([
                    'user_id' => $userId,
                    'rekening_id' => $request->rekening_id,
                    'kategori' => $request->kategori,
                    'deskripsi' => $request->deskripsi,
                    'jumlah' => $request->amount,
                    'tanggal' => $request->tanggal
                ]);
                $transaksi->jenis = 'pemasukan';
            } else {
                $transaksi = Pengeluaran::create([
                    'user_id' => $userId,
                    'rekening_id' => $request->rekening_id,
                    'kategori' => $request->kategori,
                    'deskripsi' => $request->deskripsi,
                    'jumlah' => $request->amount,
                    'tanggal' => $request->tanggal
                ]);
                $transaksi->jenis = 'pengeluaran';
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // 3. KOMPENSASI: panggil adjust-balance arah kebalikan (ref baru) untuk balikin saldo
            $compRef = (string) Str::uuid();
            $compDirection = ($request->type === 'income') ? 'debit' : 'credit';
            try {
                Http::withToken($token)
                    ->acceptJson()
                    ->timeout(10)
                    ->post($accountServiceUrl . "/api/internal/accounts/{$request->rekening_id}/adjust-balance", [
                        'amount' => $request->amount,
                        'direction' => $compDirection,
                        'ref' => $compRef
                    ]);
            } catch (\Throwable $compEx) {
                // Catat log kegagalan kompensasi yang kritis
                \Log::critical("Kompensasi saldo gagal: " . $compEx->getMessage(), [
                    'user_id' => $userId,
                    'rekening_id' => $request->rekening_id,
                    'amount' => $request->amount,
                    'direction' => $compDirection
                ]);
            }

            return response()->json([
                'message' => 'Gagal menyimpan transaksi ke database lokal. Saldo telah dikompensasi.',
                'error' => $e->getMessage()
            ], 500);
        }

        // 4. Publish event transaction.created ke Redis
        Broker::publish([
            'event' => 'transaction.created',
            'user_id' => $userId,
            'type' => $request->type,
            'rekening_id' => (int) $request->rekening_id,
            'amount' => (float) $request->amount,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'transaction_id' => $transaksi->id,
            'tanggal' => $request->tanggal
        ]);

        return response()->json($transaksi, Response::HTTP_CREATED);
    }

    /**
     * Menampilkan satu transaksi spesifik.
     */
    public function show(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $jenis = $request->query('jenis');

        if ($jenis === 'pemasukan') {
            $data = Pemasukan::where('user_id', $userId)->find($id);
            if ($data) $data->jenis = 'pemasukan';
        } elseif ($jenis === 'pengeluaran') {
            $data = Pengeluaran::where('user_id', $userId)->find($id);
            if ($data) $data->jenis = 'pengeluaran';
        } else {
            $data = Pemasukan::where('user_id', $userId)->find($id);
            if ($data) {
                $data->jenis = 'pemasukan';
            } else {
                $data = Pengeluaran::where('user_id', $userId)->find($id);
                if ($data) $data->jenis = 'pengeluaran';
            }
        }

        if (!$data) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        return response()->json($data);
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $jenis = $request->query('jenis');

        if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            return response()->json([
                'message' => 'Parameter ?jenis=pemasukan atau ?jenis=pengeluaran wajib disertakan.'
            ], 400);
        }

        if ($jenis === 'pemasukan') {
            $data = Pemasukan::where('user_id', $userId)->find($id);
        } else {
            $data = Pengeluaran::where('user_id', $userId)->find($id);
        }

        if (!$data) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $token = $request->bearerToken();
        $accountServiceUrl = rtrim(env('ACCOUNT_SERVICE_URL', 'http://account-service:8000'), '/');

        // 1. Panggil account-service untuk membalikkan efek saldo
        // Hapus pemasukan (income) -> debit saldo (dikurangi)
        // Hapus pengeluaran (expense) -> credit saldo (ditambah)
        $direction = ($jenis === 'pemasukan') ? 'debit' : 'credit';
        $ref = (string) Str::uuid();

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->post($accountServiceUrl . "/api/internal/accounts/{$data->rekening_id}/adjust-balance", [
                    'amount' => $data->jumlah,
                    'direction' => $direction,
                    'ref' => $ref
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghubungi account-service: ' . $e->getMessage()
            ], 502);
        }

        if (!$resp->successful()) {
            return response()->json(
                $resp->json() ?? ['message' => 'Terjadi kesalahan saat update saldo di account-service.'],
                $resp->status()
            );
        }

        // 2. Hapus baris di local DB
        try {
            $data->delete();
        } catch (\Throwable $e) {
            // 3. KOMPENSASI HAPUS GAGAL: Kembalikan saldo seperti semula
            $compRef = (string) Str::uuid();
            $compDirection = ($direction === 'debit') ? 'credit' : 'debit';
            try {
                Http::withToken($token)
                    ->acceptJson()
                    ->timeout(10)
                    ->post($accountServiceUrl . "/api/internal/accounts/{$data->rekening_id}/adjust-balance", [
                        'amount' => $data->jumlah,
                        'direction' => $compDirection,
                        'ref' => $compRef
                    ]);
            } catch (\Throwable $compEx) {
                \Log::critical("Kompensasi hapus transaksi gagal: " . $compEx->getMessage(), [
                    'user_id' => $userId,
                    'rekening_id' => $data->rekening_id,
                    'amount' => $data->jumlah,
                    'direction' => $compDirection
                ]);
            }

            return response()->json([
                'message' => 'Gagal menghapus transaksi dari database lokal. Saldo telah dikompensasi.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'Transaksi berhasil dihapus dan saldo diperbarui.'], 200);
    }
}
