<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'security_question' => 'nullable|string',
            'security_answer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Generate username if not provided
        $username = $request->username ?? explode('@', $request->email)[0];
        $baseUsername = $username;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        // Create User
        $user = User::create([
            'name' => $request->name ?? $username,
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'security_question' => $request->security_question,
            'security_answer' => $request->security_answer ? Hash::make(strtolower(trim($request->security_answer))) : null,
            'tipe_akun' => 'gratis',
        ]);

        // Publish user.registered event to Redis queue
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
            Log::error('Failed to publish user.registered to Redis: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check by email or username
        $user = User::where('email', $request->email)
            ->orWhere('username', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Login gagal'], 401);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'Akun Anda telah diblokir'], 403);
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->noContent(); // Returns 204
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'tipe_akun' => $user->tipe_akun,
            'is_admin' => $user->isAdmin(),
        ]);
    }
}
