<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $transfers = Transfer::with(['rekeningSumber', 'rekeningTujuan'])
            ->where('user_id', $userId)
            ->orderBy('tanggal', 'desc')
            ->get();
        
        $rekening = Rekening::where('user_id', $userId)->get();

        return view('transfer.index', compact('transfers', 'rekening'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rekening_sumber_id' => 'required',
            'rekening_tujuan_id' => 'required|different:rekening_sumber_id',
            'jumlah' => 'required|numeric|min:1'
        ]);

        DB::transaction(function() use ($request) {
            $userId = Auth::id();
            
            // 1. Kurangi Sumber
            Rekening::where('user_id', $userId)
                ->where('id', $request->rekening_sumber_id)
                ->decrement('saldo', $request->jumlah);

            // 2. Tambah Tujuan
            Rekening::where('user_id', $userId)
                ->where('id', $request->rekening_tujuan_id)
                ->increment('saldo', $request->jumlah);

            // 3. Catat
            Transfer::create([
                'user_id' => $userId,
                'rekening_sumber_id' => $request->rekening_sumber_id,
                'rekening_tujuan_id' => $request->rekening_tujuan_id,
                'jumlah' => $request->jumlah,
                'deskripsi' => $request->deskripsi,
                'tanggal' => $request->tanggal
            ]);
        });

        return back()->with('success', 'Transfer berhasil.');
    }
}