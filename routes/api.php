<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessagesController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me',       [AuthController::class, 'me']);
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::get('/messages',  [MessagesController::class, 'index']);
    Route::post('/send_message', [MessagesController::class, 'store'])->middleware('daily.limit');

    // Admin features
    Route::middleware('admin')->group(function () {
        // Metricas
        Route::get('/admin/metrics/messages', [MessagesController::class, 'adminGetUsersMetrics']);
    });
});
