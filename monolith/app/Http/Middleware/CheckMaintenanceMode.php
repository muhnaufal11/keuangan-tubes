<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class CheckMaintenanceMode
{
    public function handle($request, Closure $next)
    {
        $enabled = Setting::get('maintenance_mode', '0');
        if ($enabled == '1') {
            // allow admins (already authenticated)
            if (Auth::check() && method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin()) {
                return $next($request);
            }

            // allow admin routes to proceed (admin middleware will protect them) so admin can reach login or admin urls
            if ($request->is('admin*')) {
                return $next($request);
            }

            // Allow authentication and password reset routes so admins can login during maintenance
            $allowedPaths = [
                'login', 'logout', 'register', 'password/*', 'password', 'auth/*'
            ];
            foreach ($allowedPaths as $p) {
                if ($request->is($p)) {
                    return $next($request);
                }
            }

            // Allow static assets and storage so the maintenance page loads properly
            $assetPaths = ['css/*', 'js/*', 'images/*', 'img/*', 'storage/*', 'vendor/*'];
            foreach ($assetPaths as $p) {
                if ($request->is($p)) {
                    return $next($request);
                }
            }

            $message = Setting::get('maintenance_message', 'Sistem sedang dalam perbaikan.');
            return response()->view('maintenance', ['message' => $message]);
        }

        return $next($request);
    }
}
