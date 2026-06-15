<?php

namespace App\GraphQL\Queries;

use App\Models\Notification;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Notifications
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        return Notification::where('user_id', $uid)->latest()->get();
    }
}
