<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class Register
{
    public function __invoke($_, array $args)
    {
        $username = $args['username'];
        $email = $args['email'];

        if (User::where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'username' => ['Username sudah digunakan.'],
            ]);
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email sudah digunakan.'],
            ]);
        }

        $user = User::create([
            'name' => $args['name'] ?? $username,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($args['password']),
            'tipe_akun' => 'gratis',
        ]);

        // Publish to Redis
        $eventData = [
            'event' => 'user.registered',
            'user_id' => $user->id,
            'email' => $user->email,
            'occurred_at' => now()->toIso8601String()
        ];
        try {
            Redis::rpush(
                env('NOTIFICATIONS_QUEUE', 'queue:notifications'),
                json_encode($eventData)
            );
        } catch (\Exception $e) {
            Log::error('Redis publish error in GraphQL Register: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user
        ];
    }
}
