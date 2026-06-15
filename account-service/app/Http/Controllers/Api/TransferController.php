<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use App\Models\Transfer;
use App\Support\Broker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Transfer antar rekening ditangani DI account-service supaya ATOMIC:
 * kedua rekening ada di account_db, jadi debit+credit dalam satu DB::transaction lokal.
 */
class TransferController extends Controller
{
    private function uid(Request $r)
    {
        return $r->attributes->get('user_id');
    }

    public function index(Request $r)
    {
        return response()->json([
            'data' => Transfer::where('user_id', $this->uid($r))->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'rekening_sumber_id' => 'required|integer',
            'rekening_tujuan_id' => 'required|integer|different:rekening_sumber_id',
            'jumlah' => 'required|numeric|min:0.01',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'nullable|date',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $uid = $this->uid($r);

        $result = DB::transaction(function () use ($r, $uid) {
            // Kunci kedua rekening dalam urutan id konsisten (hindari deadlock).
            $rekenings = Rekening::where('user_id', $uid)
                ->whereIn('id', [$r->rekening_sumber_id, $r->rekening_tujuan_id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sumber = $rekenings->get((int) $r->rekening_sumber_id);
            $tujuan = $rekenings->get((int) $r->rekening_tujuan_id);
            if (!$sumber || !$tujuan) return ['status' => 404];

            $jumlah = (float) $r->jumlah;
            if ((float) $sumber->saldo < $jumlah) return ['status' => 409];

            $sumber->saldo = (float) $sumber->saldo - $jumlah;
            $sumber->save();
            $tujuan->saldo = (float) $tujuan->saldo + $jumlah;
            $tujuan->save();

            $transfer = Transfer::create([
                'user_id' => $uid,
                'rekening_sumber_id' => $sumber->id,
                'rekening_tujuan_id' => $tujuan->id,
                'jumlah' => $jumlah,
                'deskripsi' => $r->deskripsi,
                'tanggal' => $r->tanggal ?? now()->toDateString(),
            ]);

            return ['status' => 200, 'transfer' => $transfer];
        });

        if ($result['status'] === 404) return response()->json(['message' => 'Rekening sumber/tujuan tidak ditemukan'], 404);
        if ($result['status'] === 409) return response()->json(['message' => 'Saldo sumber tidak mencukupi'], 409);

        $t = $result['transfer'];
        Broker::publish([
            'event' => 'transfer.completed',
            'user_id' => $uid,
            'from_rekening_id' => $t->rekening_sumber_id,
            'to_rekening_id' => $t->rekening_tujuan_id,
            'amount' => (float) $t->jumlah,
            'transfer_id' => $t->id,
        ]);

        return response()->json(['message' => 'Transfer berhasil', 'data' => $t], 201);
    }
}
