<?php

namespace App\GraphQL\Queries;

use App\Models\Rekening;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Accounts
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        return Rekening::where('user_id', $uid)->get();
    }
}
