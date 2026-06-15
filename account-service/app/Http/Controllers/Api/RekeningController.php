<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekeningController extends Controller
{
    private function uid(Request $r)
    {
        return $r->attributes->get('user_id');
    }

    public function index(Request $r)
    {
        return response()->json(['data' => Rekening::where('user_id', $this->uid($r))->get()]);
    }

    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'nama_rekening' => 'required|string',
            'no_rekening' => 'nullable|string',
            'saldo' => 'required|numeric|min:0',
            'tipe' => 'required|string', // BANK, E-WALLET, TUNAI
            'minimum_saldo' => 'nullable|numeric|min:0',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $rekening = Rekening::create([
            'user_id' => $this->uid($r),
            'nama_rekening' => $r->nama_rekening,
            'no_rekening' => $r->no_rekening,
            'tipe' => $r->tipe,
            'saldo' => $r->saldo,
            'minimum_saldo' => $r->minimum_saldo ?? 0,
        ]);

        return response()->json(['message' => 'Rekening berhasil dibuat', 'data' => $rekening], 201);
    }

    public function show(Request $r, $id)
    {
        $rekening = Rekening::where('user_id', $this->uid($r))->findOrFail($id);
        return response()->json(['data' => $rekening]);
    }

    public function update(Request $r, $id)
    {
        $rekening = Rekening::where('user_id', $this->uid($r))->findOrFail($id);
        // Saldo TIDAK boleh diubah langsung di sini -> hanya via adjust-balance / transfer.
        $rekening->update($r->only(['nama_rekening', 'no_rekening', 'tipe', 'minimum_saldo']));
        return response()->json(['message' => 'Rekening berhasil diupdate', 'data' => $rekening]);
    }

    public function destroy(Request $r, $id)
    {
        $rekening = Rekening::where('user_id', $this->uid($r))->findOrFail($id);
        $rekening->delete();
        return response()->json(['message' => 'Rekening berhasil dihapus']);
    }

    public function balance(Request $r, $id)
    {
        $rekening = Rekening::where('user_id', $this->uid($r))->findOrFail($id);
        return response()->json(['rekening_id' => $rekening->id, 'saldo' => $rekening->saldo]);
    }
}
