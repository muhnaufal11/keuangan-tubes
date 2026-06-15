<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->attributes->get('user_id');
        $token = $request->bearerToken();
        $bulanIni = date('Y-m');

        // Agregasi local DB
        $totalPemasukan = Pemasukan::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $totalPengeluaran = Pengeluaran::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        // Panggil account-service untuk total saldo & detail rekening
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
            // Kita log error tapi tetap kembalikan data dashboard lokal yang sukses dihitung.
            \Log::error('Gagal mengambil rekening dari account-service: ' . $e->getMessage());
        }

        return response()->json([
            'bulan_ini' => [
                'periode' => date('F Y'),
                'pemasukan' => (float) $totalPemasukan,
                'pengeluaran' => (float) $totalPengeluaran,
            ],
            'total_saldo' => (float) $totalSaldo,
            'detail_rekening' => $rekenings
        ]);
    }
}
