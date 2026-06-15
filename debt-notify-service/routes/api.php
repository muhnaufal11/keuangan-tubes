<?php

use App\Http\Controllers\Api\UtangController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TipController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['service' => 'debt-notify-service', 'status' => 'ok']));

Route::middleware('remote.auth')->group(function () {
    // Debts (Utang/Piutang)
    Route::prefix('debts')->group(function () {
        Route::get('/', [UtangController::class, 'index']);
        Route::post('/', [UtangController::class, 'store']);
        Route::get('/{id}', [UtangController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}', [UtangController::class, 'destroy'])->whereNumber('id');
        Route::post('/{id}/payments', [UtangController::class, 'bayar'])->whereNumber('id');
        Route::get('/{id}/history', [UtangController::class, 'riwayat'])->whereNumber('id');
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
        Route::post('/broadcast', [NotificationController::class, 'broadcast']); // admin only (checked inside controller)
    });

    // Tips
    Route::prefix('tips')->group(function () {
        Route::get('/', [TipController::class, 'index']);
    });
});
