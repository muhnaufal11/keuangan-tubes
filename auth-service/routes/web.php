<?php

use Illuminate\Support\Facades\Route;

// Backend-only service: tidak ada route web.
Route::get('/', fn () => response()->json(['service' => 'auth-service', 'status' => 'ok']));
