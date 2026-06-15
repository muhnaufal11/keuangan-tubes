<?php

namespace App\GraphQL\Queries;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Transactions
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');

        $pemasukan = Pemasukan::where('user_id', $uid)->get()->map(function ($item) {
            $item->jenis = 'pemasukan';
            return $item;
        });

        $pengeluaran = Pengeluaran::where('user_id', $uid)->get()->map(function ($item) {
            $item->jenis = 'pengeluaran';
            return $item;
        });

        return $pemasukan->merge($pengeluaran)->sortByDesc('tanggal')->values();
    }
}
