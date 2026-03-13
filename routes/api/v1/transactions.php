<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::middleware('role:ADMIN,MANAGER,FINANCE')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    });

    Route::middleware('role:ADMIN,FINANCE')->group(function () {
        Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    });
});
