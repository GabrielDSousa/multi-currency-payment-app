<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)->name('api.health');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::get('payment/{payment}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('payment', [PaymentController::class, 'store'])->name('payment.store');
    Route::patch('payment/{payment}/approve', [PaymentController::class, 'approve'])->name('payment.approve');
    Route::patch('payment/{payment}/reject', [PaymentController::class, 'reject'])->name('payment.reject');
});
