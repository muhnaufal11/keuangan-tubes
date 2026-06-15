<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Memvalidasi Bearer token ke auth-service (sumber identitas tunggal).
 * Hasil di-cache 60 detik agar tidak memanggil auth-service tiap request.
 * Menyimpan user_id ke request->attributes untuk dipakai controller/resolver.
 *
 * Reusable: P3 (transaction) & P4 (debt-notify) bisa salin file ini.
 */
class RemoteAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $cacheKey = 'remoteauth:' . hash('sha256', $token);

        $user = Cache::remember($cacheKey, 60, function () use ($token) {
            try {
                $base = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $resp = Http::acceptJson()->withToken($token)->timeout(5)
                    ->get($base . '/api/auth/validate-token');
                return $resp->successful() ? $resp->json() : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        if (!$user || empty($user['id'])) {
            Cache::forget($cacheKey);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('user_id', $user['id']);
        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
