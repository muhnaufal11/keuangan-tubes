<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BalanceAdjustment;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoint internal yang dipanggil transaction-service (P3).
 * account-service = PEMILIK SALDO yang otoritatif.
 * lockForUpdate + idempotent berdasarkan "ref" => menyelesaikan masalah atomicity lintas service.
 */
class InternalBalanceController extends Controller
{
    public function adjust(Request $r, $id)
    {
        $v = Validator::make($r->all(), [
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:credit,debit',
            'ref' => 'required|string|max:191',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $uid = $r->attributes->get('user_id');

        // Idempotensi: kalau ref sudah pernah diproses, kembalikan hasil lama.
        $existing = BalanceAdjustment::where('ref', $r->ref)->first();
        if ($existing) {
            return response()->json([
                'rekening_id' => $existing->rekening_id,
                'saldo_baru' => $existing->saldo_after,
                'idempotent' => true,
            ]);
        }

        try {
            $result = DB::transaction(function () use ($r, $id, $uid) {
                $rek = Rekening::where('user_id', $uid)->lockForUpdate()->find($id);
                if (!$rek) return ['status' => 404];

                $amount = (float) $r->amount;
                if ($r->direction === 'debit') {
                    if ((float) $rek->saldo < $amount) return ['status' => 409];
                    $rek->saldo = (float) $rek->saldo - $amount;
                } else {
                    $rek->saldo = (float) $rek->saldo + $amount;
                }
                $rek->save();

                BalanceAdjustment::create([
                    'ref' => $r->ref,
                    'rekening_id' => $rek->id,
                    'amount' => $amount,
                    'direction' => $r->direction,
                    'saldo_after' => $rek->saldo,
                ]);

                return ['status' => 200, 'rekening_id' => $rek->id, 'saldo_baru' => $rek->saldo];
            });

            if ($result['status'] === 404) return response()->json(['message' => 'Rekening tidak ditemukan'], 404);
            if ($result['status'] === 409) return response()->json(['message' => 'Saldo tidak mencukupi'], 409);

            return response()->json([
                'rekening_id' => $result['rekening_id'],
                'saldo_baru' => $result['saldo_baru'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race pada unique(ref) -> perlakukan sebagai idempotent.
            $existing = BalanceAdjustment::where('ref', $r->ref)->first();
            if ($existing) {
                return response()->json([
                    'rekening_id' => $existing->rekening_id,
                    'saldo_baru' => $existing->saldo_after,
                    'idempotent' => true,
                ]);
            }
            throw $e;
        }
    }
}
