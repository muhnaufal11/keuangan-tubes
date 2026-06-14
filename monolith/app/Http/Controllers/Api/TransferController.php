<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    public function index()
    {
        $transfers = Transfer::with(['rekeningSumber', 'rekeningTujuan'])
            ->where('user_id', Auth::id())
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        return response()->json($transfers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rekening_sumber_id' => 'required|exists:rekenings,id',
            'rekening_tujuan_id' => 'required|exists:rekenings,id|different:rekening_sumber_id',
            'jumlah' => 'required|numeric|min:1',
            'tanggal' => 'required|date',
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        try {
            DB::transaction(function() use ($request) {
                $userId = Auth::id();

                // Cek Saldo Sumber
                $sumber = Rekening::where('user_id', $userId)->lockForUpdate()->find($request->rekening_sumber_id);

                if (!$sumber) throw new \Exception("Rekening sumber tidak valid.");

                // Cek kecukupan saldo (Optional: Anda bisa menambahkan logika minimum saldo disini)
                if ($sumber->saldo < $request->jumlah) {
                    throw new \Exception("Saldo rekening sumber tidak mencukupi.");
                }

                // 1. Kurangi Sumber
                $sumber->decrement('saldo', $request->jumlah);

                // 2. Tambah Tujuan
                Rekening::where('user_id', $userId)
                    ->where('id', $request->rekening_tujuan_id)
                    ->increment('saldo', $request->jumlah);

                // 3. Catat History
                Transfer::create([
                    'user_id' => $userId,
                    'rekening_sumber_id' => $request->rekening_sumber_id,
                    'rekening_tujuan_id' => $request->rekening_tujuan_id,
                    'jumlah' => $request->jumlah,
                    'deskripsi' => $request->deskripsi,
                    'tanggal' => $request->tanggal
                ]);
            });

            return response()->json(['message' => 'Transfer berhasil dilakukan'], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Transfer gagal', 'error' => $e->getMessage()], 400);
        }
    }
}
