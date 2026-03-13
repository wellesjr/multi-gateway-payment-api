<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::middleware('can:products.manage')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
    });

    Route::middleware('can:products.view')->group(function () {
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });

    Route::middleware('can:products.delete')->group(function () {
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
});