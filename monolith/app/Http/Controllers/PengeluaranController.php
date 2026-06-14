<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\Rekening;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PengeluaranController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = Pengeluaran::where('user_id', $userId)->with('rekening');

        if ($request->filter == 'harian') $query->whereDate('tanggal', today());
        elseif ($request->filter == 'mingguan') $query->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()]);
        elseif ($request->filter == 'bulanan') $query->whereMonth('tanggal', date('m'));

        $pengeluaran = $query->orderBy('tanggal', 'desc')->get();
        
        // Pastikan Model KategoriPengeluaran sudah dibuat, jika belum bisa pakai array manual atau model Kategori biasa
        // Cek apakah Anda punya model App\Models\KategoriPengeluaran, jika tidak ubah ke App\Models\Kategori
        $kategori = Kategori::where('user_id', $userId)->get(); 
        
        $rekening = Rekening::where('user_id', $userId)->get();

        return view('pengeluaran.index', compact('pengeluaran', 'kategori', 'rekening'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jumlah' => 'required|numeric',
            'rekening_id' => 'required|exists:rekening,id',
            'kategori' => 'required',
            'tanggal' => 'required'
        ]);

        DB::transaction(function() use ($request) {
            $userId = Auth::id();
            // Cek dan kunci rekening untuk update saldo aman
            $rekening = Rekening::where('user_id', $userId)->lockForUpdate()->find($request->rekening_id);

            if (!$rekening) {
                throw new \Exception('Rekening tidak ditemukan atau tidak valid.');
            }

            // LOGIKA PERBAIKAN UX:
            // Jika kategori dikirim sebagai ID (angka), ambil nama kategorinya untuk deskripsi default
            $namaKategori = $request->kategori;
            if (is_numeric($request->kategori)) {
                $kategoriDb = Kategori::find($request->kategori);
                if ($kategoriDb) $namaKategori = $kategoriDb->nama_kategori;
            }

            $deskripsiFinal = $request->deskripsi ? $request->deskripsi : $namaKategori;

            Pengeluaran::create([
                'user_id' => $userId,
                'kategori' => $namaKategori, // Fix: Simpan Nama Kategori (Teks), bukan ID (Angka)
                'deskripsi' => $deskripsiFinal, 
                'jumlah' => $request->jumlah,
                'rekening_id' => $request->rekening_id,
                'tanggal' => $request->tanggal
            ]);

            // Kurangi Saldo Rekening
            $rekening->saldo -= $request->jumlah;
            $rekening->save();
        });

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    // Method Baru: Tambah Kategori Pengeluaran
    public function storeKategori(Request $request)
    {
        $request->validate(['nama_kategori' => 'required']);
        Kategori::create([
            'user_id' => Auth::id(),
            'nama_kategori' => $request->nama_kategori
        ]);
        return back()->with('success', 'Kategori pengeluaran baru ditambahkan.');
    }

    public function destroy($id)
    {
        DB::transaction(function() use ($id) {
            $data = Pengeluaran::where('user_id', Auth::id())->findOrFail($id);
            // Kembalikan Saldo (Refund) ke rekening
            Rekening::where('id', $data->rekening_id)->increment('saldo', $data->jumlah);
            $data->delete();
        });

        return back()->with('success', 'Data dihapus dan saldo dikembalikan.');
    }
}