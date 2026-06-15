<?php

namespace App\GraphQL\Mutations;

use App\Models\Notification;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class MarkRead
{
    public function __invoke($root, array $args, GraphQLContext $context)
    {
        $uid = $context->request()->attributes->get('user_id');
        $id = $args['id'];

        $notification = Notification::where('user_id', $uid)->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return $notification;
    }
}
