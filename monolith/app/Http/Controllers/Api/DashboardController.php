<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekening;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $bulanIni = date('Y-m');

        $totalPemasukan = Pemasukan::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $totalPengeluaran = Pengeluaran::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $rekenings = Rekening::where('user_id', $userId)->get();
        $totalSaldo = $rekenings->sum('saldo');

        return response()->json([
            'bulan_ini' => [
                'periode' => date('F Y'),
                'pemasukan' => $totalPemasukan,
                'pengeluaran' => $totalPengeluaran,
            ],
            'total_saldo' => $totalSaldo,
            'detail_rekening' => $rekenings
        ]);
    }
}
