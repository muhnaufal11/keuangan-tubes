<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // 1. Ambil Filter dari Request, default ke Bulan & Tahun saat ini
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        // 2. Hitung Total Pemasukan & Pengeluaran (Berdasarkan Filter)
        $totalPemasukan = Pemasukan::where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $totalPengeluaran = Pengeluaran::where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $selisih = $totalPemasukan - $totalPengeluaran;

        // 3. Siapkan Data untuk Grafik (Group by Kategori)
        $pemasukanPerKategori = Pemasukan::select('kategori', DB::raw('sum(jumlah) as total'))
            ->where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('kategori')
            ->get();

        $pengeluaranPerKategori = Pengeluaran::select('kategori', DB::raw('sum(jumlah) as total'))
            ->where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->groupBy('kategori')
            ->get();

        // 4. Ambil Detail Transaksi (Digabung & Diurutkan)
        // Kita ambil keduanya, kasih label 'jenis', lalu gabung (merge)
        $dataPemasukan = Pemasukan::where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with('rekening')
            ->get()
            ->map(function($item) {
                $item->jenis = 'pemasukan'; // Penanda
                return $item;
            });

        $dataPengeluaran = Pengeluaran::where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with('rekening')
            ->get()
            ->map(function($item) {
                $item->jenis = 'pengeluaran'; // Penanda
                return $item;
            });

        // Gabung dan urutkan berdasarkan tanggal (descending)
        $transaksi = $dataPemasukan->merge($dataPengeluaran)->sortByDesc('tanggal');

        return view('laporan.index', compact(
            'bulan', 'tahun', 
            'totalPemasukan', 'totalPengeluaran', 'selisih',
            'pemasukanPerKategori', 'pengeluaranPerKategori',
            'transaksi'
        ));
    }
}