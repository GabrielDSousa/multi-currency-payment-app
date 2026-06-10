<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;

// Health check público — usado por Docker, load balancers, uptime monitors
Route::get('/health', HealthCheckController::class)->name('api.health');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
