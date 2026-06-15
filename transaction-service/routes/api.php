<?php

use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['service' => 'transaction-service', 'status' => 'ok']));

Route::middleware('remote.auth')->group(function () {
    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransaksiController::class, 'index']);
        Route::post('/', [TransaksiController::class, 'store']);
        Route::get('/{id}', [TransaksiController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}', [TransaksiController::class, 'destroy'])->whereNumber('id');
    });

    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'index']);
});
