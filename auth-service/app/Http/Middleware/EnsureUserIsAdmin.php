<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->tipe_akun !== 'admin') {
            return redirect()->route('login')->with('error', 'Akses admin diperlukan.');
        }

        return $next($request);
    }
}
