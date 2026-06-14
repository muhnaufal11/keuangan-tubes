<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\Admin\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Stateless Password Reset via Security Question
    Route::post('/password/question', [PasswordResetController::class, 'getQuestion']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetWithQuestion']);
    
    // Protected Authentication Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/validate-token', [AuthController::class, 'validateToken']);
        
        // Admin user management routes
        Route::middleware('api.admin')->prefix('admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::patch('/users/{id}/ban', [UserController::class, 'ban']);
        });
    });
});
