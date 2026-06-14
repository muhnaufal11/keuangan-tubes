<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Models\Rekening;
use App\Models\Kategori; // Model untuk tabel 'kategori' (Pemasukan)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PemasukanController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = Pemasukan::where('user_id', $userId)->with('rekening');

        // Logic Filter
        if ($request->filter == 'harian') $query->whereDate('tanggal', today());
        elseif ($request->filter == 'mingguan') $query->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()]);
        elseif ($request->filter == 'bulanan') $query->whereMonth('tanggal', date('m'));

        $pemasukan = $query->orderBy('tanggal', 'desc')->get();
        $kategori = Kategori::where('user_id', $userId)->get();
        $rekening = Rekening::where('user_id', $userId)->where('tipe', '!=', 'TUNAI')->get();

        return view('pemasukan.index', compact('pemasukan', 'kategori', 'rekening'));
    }

    public function store(Request $request)
    {
        DB::transaction(function() use ($request) {
            Pemasukan::create([
                'user_id' => Auth::id(),
                'kategori' => $request->kategori,
                'deskripsi' => ($request->kategori == 'Lain-lain') ? $request->deskripsi : $request->kategori,
                'jumlah' => $request->jumlah,
                'rekening_id' => $request->rekening_id,
                'tanggal' => $request->tanggal
            ]);

            // TAMBAH saldo rekening
            Rekening::where('id', $request->rekening_id)->increment('saldo', $request->jumlah);
        });

        return back()->with('success', 'Pemasukan berhasil dicatat.');
    }

    // Method Baru: Tambah Kategori Pemasukan
    public function storeKategori(Request $request)
    {
        $request->validate(['nama_kategori' => 'required']);
        Kategori::create([
            'user_id' => Auth::id(),
            'nama_kategori' => $request->nama_kategori
        ]);
        return back()->with('success', 'Kategori baru ditambahkan.');
    }

    public function destroy($id)
    {
        DB::transaction(function() use ($id) {
            $data = Pemasukan::where('user_id', Auth::id())->findOrFail($id);
            // Revert (KURANGI) saldo karena data dihapus
            Rekening::where('id', $data->rekening_id)->decrement('saldo', $data->jumlah);
            $data->delete();
        });

        return back()->with('success', 'Data dihapus dan saldo dikembalikan.');
    }
}