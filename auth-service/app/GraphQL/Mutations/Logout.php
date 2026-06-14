<?php

namespace App\GraphQL\Mutations;

use Illuminate\Support\Facades\Auth;

class Logout
{
    public function __invoke($_, array $args)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
            return [
                'status' => 'SUCCESS',
                'message' => 'Logout berhasil'
            ];
        }

        return [
            'status' => 'FAILED',
            'message' => 'Tidak terautentikasi'
        ];
    }
}
