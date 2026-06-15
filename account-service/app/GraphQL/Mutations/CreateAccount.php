<?php

namespace App\GraphQL\Mutations;

use App\Models\Rekening;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateAccount
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');

        return Rekening::create([
            'user_id' => $uid,
            'nama_rekening' => $args['nama_rekening'],
            'tipe' => $args['tipe'],
            'saldo' => $args['saldo'],
            'no_rekening' => $args['no_rekening'] ?? null,
            'minimum_saldo' => 0,
        ]);
    }
}
