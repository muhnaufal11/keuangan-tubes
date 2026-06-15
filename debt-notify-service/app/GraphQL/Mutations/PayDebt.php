<?php

namespace App\GraphQL\Mutations;

use App\Models\Utang;
use App\Models\RiwayatUtang;
use App\Support\Broker;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PayDebt
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        $id = $args['id'];
        $amount = $args['jumlah_bayar'];

        if ($amount <= 0) {
            throw new Error("Jumlah bayar harus lebih dari 0.");
        }

        $utang = Utang::where('user_id', $uid)->findOrFail($id);

        if ($amount > $utang->sisa_jumlah) {
            throw new Error("Jumlah bayar melebihi sisa tagihan (Sisa: {$utang->sisa_jumlah}).");
        }

        try {
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

            $utang->refresh();

            // Publish event to Redis Queue
            Broker::publish([
                'event' => 'debt.payment_made',
                'user_id' => $uid,
                'utang_id' => $utang->id,
                'amount' => $amount,
                'sisa' => $utang->sisa_jumlah,
                'occurred_at' => now()->toIso8601String()
            ]);

            return $utang;

        } catch (\Throwable $e) {
            throw new Error("Gagal memproses pembayaran: " . $e->getMessage());
        }
    }
}
