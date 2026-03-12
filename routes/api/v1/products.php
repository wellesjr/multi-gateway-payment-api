<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::middleware('role:ADMIN,MANAGER')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
    });

    Route::middleware('role:ADMIN,MANAGER,FINANCE')->group(function () {
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });

    Route::middleware('role:ADMIN,MANAGER')->group(function () {
        Route::put('/products/{product}', [ProductController::class, 'update']);
    });

    Route::middleware('role:ADMIN')->group(function () {
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
});