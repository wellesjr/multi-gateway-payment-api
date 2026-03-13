<?php

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api', 'can:gateways.manage'])->group(function () {
    Route::get('/gateways', [GatewayController::class, 'index']);
    Route::patch('/gateways/{gateway}/status', [GatewayController::class, 'updateStatus']);
    Route::patch('/gateways/{gateway}/priority', [GatewayController::class, 'updatePriority']);
});