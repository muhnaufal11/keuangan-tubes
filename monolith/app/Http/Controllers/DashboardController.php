<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Rekening;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Transfer;
use App\Models\Tip;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $bulanIni = date('Y-m');

        // Total Pemasukan & Pengeluaran Bulan Ini
        $totalPemasukan = Pemasukan::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        $totalPengeluaran = Pengeluaran::where('user_id', $userId)
            ->where('tanggal', 'like', "$bulanIni%")
            ->sum('jumlah');

        // Data Rekening
        $rekeningList = Rekening::where('user_id', $userId)->get();
        
        // Saldo yg bisa dipakai (Saldo - Minimum Saldo)
        $totalSaldoBisaDipakai = $rekeningList->sum(function($rek) {
            return $rek->saldo - $rek->minimum_saldo;
        });

        // Saldo Tunai (Uang cash di tangan)
        $saldoTunai = $rekeningList->where('tipe', 'TUNAI')->sum('saldo');

        // Select one random active tip (publish_at null or <= now)
        $tip = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('tips')) {
                $tip = Tip::where('is_active', 1)
                    ->where(function($q){
                        $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
                    })->inRandomOrder()->first();
            }
        } catch (\Throwable $e) {
            // If something goes wrong (table missing, DB issues), log and continue without tip
            \Illuminate\Support\Facades\Log::warning('Failed to fetch tip for dashboard: ' . $e->getMessage());
            $tip = null;
        }

        // Debug point before view
        // dd('DEBUG: Data fetched, entering view', compact('totalPemasukan', 'totalPengeluaran', 'tip'));

        return view('dashboard.index', compact(
            'totalPemasukan', 'totalPengeluaran', 'rekeningList', 
            'totalSaldoBisaDipakai', 'saldoTunai', 'tip'
        ));
    }

    public function tarikTunai(Request $request)
    {
        $request->validate([
            'jumlah_tarik' => 'required|numeric|min:1',
            'rekening_sumber_id' => 'required'
        ]);

        // Gunakan Try-Catch untuk menangkap error dan menjadikannya notifikasi
        try {
            DB::transaction(function() use ($request) {
                $userId = Auth::id();
                
                // 1. Cari Rekening Sumber dan Lock Row
                $sumber = Rekening::where('user_id', $userId)->lockForUpdate()->findOrFail($request->rekening_sumber_id);
                
                // 2. Cari Rekening Tujuan (Dompet Tunai) dan Lock Row
                $tujuan = Rekening::where('user_id', $userId)->where('tipe', 'TUNAI')->lockForUpdate()->first();

                if(!$tujuan) {
                    throw new \Exception("Anda belum punya rekening tipe TUNAI (Dompet). Silakan buat di menu Rekening.");
                }

                // 3. Hitung Saldo yang Boleh Dipakai
                // Rumus: Saldo Sekarang - Saldo Minimum (Default 50.000)
                $saldoBisaDipakai = $sumber->saldo - $sumber->minimum_saldo;

                // 4. Cek Apakah Cukup?
                if ($saldoBisaDipakai < $request->jumlah_tarik) {
                    // Pesan ini yang akan muncul sebagai notifikasi merah di dashboard
                    throw new \Exception("Saldo tidak cukup! Sisa yang bisa ditarik: Rp " . number_format($saldoBisaDipakai, 0, ',', '.'));
                }

                // 5. Proses Tarik Tunai (Update Database)
                $sumber->saldo -= $request->jumlah_tarik;
                $sumber->save();

                $tujuan->saldo += $request->jumlah_tarik;
                $tujuan->save();

                // 6. Catat History Transfer
                Transfer::create([
                    'user_id' => $userId,
                    'jumlah' => $request->jumlah_tarik,
                    'rekening_sumber_id' => $sumber->id,
                    'rekening_tujuan_id' => $tujuan->id,
                    'deskripsi' => 'Tarik tunai ke dompet',
                    'tanggal' => now()
                ]);
            });

            // Jika Berhasil
            return back()->with('success', 'Penarikan tunai berhasil!');

        } catch (\Exception $e) {
            // Jika Gagal (Saldo kurang, dll), kembali ke dashboard dengan pesan notifikasi error
            return back()->with('error', $e->getMessage());
        }
    }
}