<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RekeningController extends Controller
{
    public function index()
    {
        $rekening = Rekening::where('user_id', Auth::id())->get();
        return response()->json(['data' => $rekening]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'saldo' => 'required|numeric',
            'tipe' => 'required|string' // BANK, EWALLET, atau TUNAI
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $rekening = Rekening::create([
            'user_id' => Auth::id(),
            'nama_rekening' => $request->nama_rekening,
            'no_rekening' => $request->no_rekening,
            'tipe' => $request->tipe,
            'saldo' => $request->saldo
        ]);

        return response()->json(['message' => 'Rekening berhasil dibuat', 'data' => $rekening], 201);
    }

    public function update(Request $request, $id)
    {
        $rekening = Rekening::where('user_id', Auth::id())->findOrFail($id);
        $rekening->update($request->all());
        return response()->json(['message' => 'Rekening berhasil diupdate', 'data' => $rekening]);
    }

    public function destroy($id)
    {
        $rekening = Rekening::where('user_id', Auth::id())->findOrFail($id);
        $rekening->delete();
        return response()->json(['message' => 'Rekening berhasil dihapus']);
    }
}
