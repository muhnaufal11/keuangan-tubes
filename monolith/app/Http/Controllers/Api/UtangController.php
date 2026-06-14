<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utang;
use App\Models\Rekening;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\RiwayatUtang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UtangController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $utang = Utang::where('user_id', $userId)
            ->where('jenis', 'utang')
            ->orderBy('status', 'asc')
            ->get();

        $piutang = Utang::where('user_id', $userId)
            ->where('jenis', 'piutang')
            ->orderBy('status', 'asc')
            ->get();

        return response()->json([
            'utang' => $utang,
            'piutang' => $piutang
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required|string',
            'jumlah' => 'required|numeric|min:1',
            'jenis' => 'required|in:utang,piutang',
            'jatuh_tempo' => 'nullable|date'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $data = Utang::create([
            'user_id' => Auth::id(),
            'jenis' => $request->jenis,
            'deskripsi' => $request->deskripsi,
            'jumlah' => $request->jumlah,
            'sisa_jumlah' => $request->jumlah, // Sisa awal = jumlah total
            'jatuh_tempo' => $request->jatuh_tempo,
            'status' => 'Belum Lunas'
        ]);

        return response()->json(['message' => 'Data berhasil dicatat', 'data' => $data], 201);
    }

    // Fitur Bayar Utang / Terima Piutang
    public function bayar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_utang' => 'required|exists:utangs,id',
            'rekening_id' => 'required|exists:rekenings,id',
            'jumlah_bayar' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        try {
            DB::transaction(function() use ($request) {
                $userId = Auth::id();
                $utang = Utang::where('user_id', $userId)->lockForUpdate()->findOrFail($request->id_utang);

                if ($request->jumlah_bayar > $utang->sisa_jumlah) {
                    throw new \Exception("Jumlah bayar melebihi sisa tagihan (Sisa: " . $utang->sisa_jumlah . ")");
                }

                // Update Sisa Utang
                $sisaBaru = $utang->sisa_jumlah - $request->jumlah_bayar;
                $utang->update([
                    'sisa_jumlah' => $sisaBaru,
                    'status' => ($sisaBaru <= 0) ? 'Lunas' : 'Belum Lunas'
                ]);

                // Update Saldo Rekening
                $rekening = Rekening::where('user_id', $userId)->where('id', $request->rekening_id)->lockForUpdate()->first();

                if ($utang->jenis == 'piutang') {
                    // Kita menerima uang -> Saldo Tambah -> Catat Pemasukan
                    $rekening->increment('saldo', $request->jumlah_bayar);

                    Pemasukan::create([
                        'user_id' => $userId,
                        'rekening_id' => $request->rekening_id,
                        'kategori' => 'Penerimaan Piutang',
                        'deskripsi' => "Pelunasan: " . $utang->deskripsi,
                        'jumlah' => $request->jumlah_bayar,
                        'tanggal' => now()
                    ]);
                } else {
                    // Kita membayar utang -> Saldo Kurang -> Catat Pengeluaran
                    if ($rekening->saldo < $request->jumlah_bayar) {
                        throw new \Exception("Saldo rekening tidak mencukupi untuk membayar utang.");
                    }

                    $rekening->decrement('saldo', $request->jumlah_bayar);

                    Pengeluaran::create([
                        'user_id' => $userId,
                        'rekening_id' => $request->rekening_id,
                        'kategori' => 'Pembayaran Utang',
                        'deskripsi' => "Bayar: " . $utang->deskripsi,
                        'jumlah' => $request->jumlah_bayar,
                        'tanggal' => now()
                    ]);
                }

                // Catat Riwayat Cicilan
                RiwayatUtang::create([
                    'utang_id' => $utang->id,
                    'jumlah' => $request->jumlah_bayar,
                    'tanggal' => now(),
                    'keterangan' => $utang->jenis == 'piutang' ? 'Terima Pembayaran' : 'Bayar Cicilan'
                ]);
            });

            return response()->json(['message' => 'Pembayaran berhasil diproses']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses pembayaran', 'error' => $e->getMessage()], 400);
        }
    }

    public function riwayat($id)
    {
        $riwayat = RiwayatUtang::where('utang_id', $id)
            ->whereHas('utang', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $riwayat]);
    }

    public function destroy($id)
    {
        $utang = Utang::where('user_id', Auth::id())->findOrFail($id);
        $utang->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
