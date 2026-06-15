<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utang;
use App\Models\RiwayatUtang;
use App\Support\Broker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UtangController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->attributes->get('user_id');

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
        $userId = $request->attributes->get('user_id');

        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required|string',
            'jumlah' => 'required|numeric|min:1',
            'jenis' => 'required|in:utang,piutang',
            'pemberi' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'jatuh_tempo' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = Utang::create([
            'user_id' => $userId,
            'jenis' => $request->jenis,
            'pemberi' => $request->pemberi,
            'deskripsi' => $request->deskripsi,
            'jumlah' => $request->jumlah,
            'sisa_jumlah' => $request->jumlah, // Sisa awal = jumlah total
            'keterangan' => $request->keterangan,
            'tanggal' => $request->tanggal ?? now()->toDateString(),
            'jatuh_tempo' => $request->jatuh_tempo,
            'status' => 'Belum Lunas'
        ]);

        return response()->json([
            'message' => 'Data berhasil dicatat',
            'data' => $data
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $utang = Utang::where('user_id', $userId)->findOrFail($id);
        return response()->json($utang);
    }

    public function bayar(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        
        $validator = Validator::make($request->all(), [
            'jumlah_bayar' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $utang = Utang::where('user_id', $userId)->findOrFail($id);
            $amount = $request->jumlah_bayar;

            if ($amount > $utang->sisa_jumlah) {
                return response()->json([
                    'message' => 'Gagal memproses pembayaran',
                    'error' => "Jumlah bayar melebihi sisa tagihan (Sisa: {$utang->sisa_jumlah})"
                ], 400);
            }

            DB::transaction(function() use ($utang, $amount) {
                $sisaBaru = $utang->sisa_jumlah - $amount;
                $utang->update([
                    'sisa_jumlah' => $sisaBaru,
                    'status' => ($sisaBaru <= 0) ? 'Lunas' : 'Belum Lunas'
                ]);

                RiwayatUtang::create([
                    'utang_id' => $utang->id,
                    'jumlah' => $amount,
                    'tanggal' => now(),
                    'keterangan' => $utang->jenis == 'piutang' ? 'Terima Pembayaran' : 'Bayar Cicilan'
                ]);
            });

            // Reload model to get fresh status and sisa_jumlah
            $utang->refresh();

            // Publish event to message broker (Redis Queue)
            Broker::publish([
                'event' => 'debt.payment_made',
                'user_id' => $userId,
                'utang_id' => $utang->id,
                'amount' => $amount,
                'sisa' => $utang->sisa_jumlah,
                'occurred_at' => now()->toIso8601String()
            ]);

            return response()->json([
                'message' => 'Pembayaran berhasil diproses',
                'data' => $utang
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memproses pembayaran',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function riwayat(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');

        // Verify ownership
        $utang = Utang::where('user_id', $userId)->findOrFail($id);

        $riwayat = RiwayatUtang::where('utang_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $riwayat]);
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $utang = Utang::where('user_id', $userId)->findOrFail($id);
        $utang->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
