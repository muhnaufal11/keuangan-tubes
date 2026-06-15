<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InternalBalanceController;
use App\Http\Controllers\Api\RekeningController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['service' => 'account-service', 'status' => 'ok']));

Route::middleware('remote.auth')->group(function () {
    // Rekening
    Route::prefix('accounts')->group(function () {
        Route::get('/', [RekeningController::class, 'index']);
        Route::post('/', [RekeningController::class, 'store']);
        Route::get('/{id}', [RekeningController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [RekeningController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [RekeningController::class, 'destroy'])->whereNumber('id');
        Route::get('/{id}/balance', [RekeningController::class, 'balance'])->whereNumber('id');
    });

    // Endpoint internal (dipanggil transaction-service / P3)
    Route::prefix('internal/accounts')->group(function () {
        Route::post('/{id}/adjust-balance', [InternalBalanceController::class, 'adjust'])->whereNumber('id');
    });

    // Kategori
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/defaults', [CategoryController::class, 'defaults']);
        Route::post('/sync-defaults', [CategoryController::class, 'syncDefaults']);
    });

    // Transfer antar rekening (atomic + publish event)
    Route::prefix('transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index']);
        Route::post('/', [TransferController::class, 'store']);
    });
});
