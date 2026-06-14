<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    /**
     * Menampilkan daftar semua transaksi.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Ambil data dari Pemasukan dan Pengeluaran
        $pemasukan = Pemasukan::where('user_id', $userId)->get()->map(function($item){ $item->jenis = 'pemasukan'; return $item; });
        $pengeluaran = Pengeluaran::where('user_id', $userId)->get()->map(function($item){ $item->jenis = 'pengeluaran'; return $item; });
        
        // Gabung dan sort
        $merged = $pemasukan->merge($pengeluaran)->sortByDesc('tanggal')->values();
        
        return response()->json($merged);
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deskripsi' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'tanggal' => 'required|date',
            'rekening_id' => 'required|exists:rekening,id',
            'kategori' => 'nullable|string'
        ]);

        $userId = Auth::id();
        $transaksi = null;

        try {
            DB::transaction(function() use ($request, $userId, &$transaksi) {
                $rekening = Rekening::where('user_id', $userId)->lockForUpdate()->findOrFail($request->rekening_id);
                
                if ($request->jenis == 'pemasukan') {
                    $transaksi = Pemasukan::create([
                        'user_id' => $userId,
                        'rekening_id' => $request->rekening_id,
                        'kategori' => $request->kategori ?? 'Umum',
                        'deskripsi' => $request->deskripsi,
                        'jumlah' => $request->jumlah,
                        'tanggal' => $request->tanggal
                    ]);
                    $rekening->increment('saldo', $request->jumlah);
                } else {
                    $transaksi = Pengeluaran::create([
                        'user_id' => $userId,
                        'rekening_id' => $request->rekening_id,
                        'kategori' => $request->kategori ?? 'Umum',
                        'deskripsi' => $request->deskripsi,
                        'jumlah' => $request->jumlah,
                        'tanggal' => $request->tanggal
                    ]);
                    $rekening->decrement('saldo', $request->jumlah);
                }
            });

            return response()->json($transaksi, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan satu transaksi spesifik.
     */
    public function show(Request $request, $id)
    {
        // Fix: Tambahkan parameter ?jenis=... untuk menghindari tabrakan ID antara Pemasukan & Pengeluaran
        $jenis = $request->query('jenis');

        if ($jenis === 'pemasukan') {
            $data = Pemasukan::find($id);
        } elseif ($jenis === 'pengeluaran') {
            $data = Pengeluaran::find($id);
        } else {
            $data = Pemasukan::find($id) ?? Pengeluaran::find($id);
        }
        
        if(!$data) return response()->json(['message' => 'Not found'], 404);
        return response()->json($data);
    }

    /**
     * Memperbarui transaksi yang ada.
     */
    public function update(Request $request, $id)
    {
        // Update via API dinonaktifkan sementara untuk mencegah inkonsistensi saldo kompleks
        return response()->json(['message' => 'Update via API not supported yet. Please use Dashboard.'], 501);
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy(Request $request, $id)
    {
        // Fix: Implementasi Delete dengan rollback saldo
        $jenis = $request->query('jenis');
        
        if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            return response()->json(['message' => 'Parameter ?jenis=pemasukan atau ?jenis=pengeluaran wajib disertakan.'], 400);
        }

        try {
            DB::transaction(function() use ($id, $jenis) {
                if ($jenis === 'pemasukan') {
                    $data = Pemasukan::where('user_id', Auth::id())->findOrFail($id);
                    // Fix: Gunakan lockForUpdate untuk konsistensi saldo
                    Rekening::where('id', $data->rekening_id)->lockForUpdate()->first()->decrement('saldo', $data->jumlah);
                    $data->delete();
                } else {
                    $data = Pengeluaran::where('user_id', Auth::id())->findOrFail($id);
                    // Fix: Gunakan lockForUpdate untuk konsistensi saldo
                    Rekening::where('id', $data->rekening_id)->lockForUpdate()->first()->increment('saldo', $data->jumlah);
                    $data->delete();
                }
            });
            return response()->json(['message' => 'Transaksi berhasil dihapus dan saldo diperbarui.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }
}
