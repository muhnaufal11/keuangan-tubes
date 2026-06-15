<?php

namespace App\GraphQL\Queries;

use App\Models\Utang;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Debts
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        return Utang::where('user_id', $uid)->get();
    }
}
