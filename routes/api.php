<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;

// Health check público — usado por Docker, load balancers, uptime monitors
Route::get('/health', HealthCheckController::class)->name('api.health');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('payment-requests/{payment}', [PaymentController::class, 'show'])->name('payment-requests.show');
    Route::get('payment-requests', [PaymentController::class, 'index'])->name('payment-requests.index');
    Route::patch('payment-requests/{payment}/approve', [PaymentController::class, 'approve']);
    Route::patch('payment-requests/{payment}/reject',  [PaymentController::class, 'reject']);
});
