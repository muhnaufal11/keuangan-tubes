<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemasukan;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PemasukanController extends Controller
{
    public function index()
    {
        $data = Pemasukan::where('user_id', Auth::id())
            ->with('rekening')
            ->orderBy('tanggal', 'desc')
            ->paginate(10); // Menggunakan pagination agar ringan
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jumlah' => 'required|numeric|min:1',
            'rekening_id' => 'required|exists:rekenings,id',
            'kategori' => 'required|string',
            'tanggal' => 'required|date',
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        try {
            DB::transaction(function() use ($request) {
                // 1. Simpan Data
                Pemasukan::create([
                    'user_id' => Auth::id(),
                    'kategori' => $request->kategori,
                    'deskripsi' => $request->deskripsi ?? $request->kategori,
                    'jumlah' => $request->jumlah,
                    'rekening_id' => $request->rekening_id,
                    'tanggal' => $request->tanggal
                ]);

                // 2. Update Saldo Rekening (Bertambah)
                Rekening::where('id', $request->rekening_id)
                    ->increment('saldo', $request->jumlah);
            });

            return response()->json(['message' => 'Pemasukan berhasil dicatat'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $data = Pemasukan::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($data);
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $pemasukan = Pemasukan::where('user_id', Auth::id())->findOrFail($id);

                // Kembalikan saldo (Kurangi) sebelum hapus
                Rekening::where('id', $pemasukan->rekening_id)
                    ->decrement('saldo', $pemasukan->jumlah);

                $pemasukan->delete();
            });
            return response()->json(['message' => 'Data dihapus dan saldo dikembalikan']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus', 'error' => $e->getMessage()], 500);
        }
    }
}
