<?php

namespace App\GraphQL\Queries;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Http;

class Summary
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        $token = $context->request()->bearerToken();
        $bulanIni = date('Y-m');

        $totalPemasukan = Pemasukan::where('user_id', $uid)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $totalPengeluaran = Pengeluaran::where('user_id', $uid)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $accountServiceUrl = rtrim(env('ACCOUNT_SERVICE_URL', 'http://account-service:8000'), '/');
        $rekenings = [];
        $totalSaldo = 0.0;

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get($accountServiceUrl . '/api/accounts');

            if ($resp->successful()) {
                $data = $resp->json();
                $rekenings = $data['data'] ?? [];
                $totalSaldo = collect($rekenings)->sum('saldo');
            }
        } catch (\Throwable $e) {
            \Log::error('GraphQL Summary: Gagal mengambil rekening dari account-service: ' . $e->getMessage());
        }

        return [
            'bulan_ini' => [
                'periode' => date('F Y'),
                'pemasukan' => (float) $totalPemasukan,
                'pengeluaran' => (float) $totalPengeluaran,
            ],
            'total_saldo' => (float) $totalSaldo,
            'detail_rekening' => $rekenings
        ];
    }
}
