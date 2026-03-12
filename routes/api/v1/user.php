<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    Route::middleware('role:ADMIN,MANAGER')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
    });

    Route::middleware('role:ADMIN,MANAGER,FINANCE')->group(function () {
        Route::get('/users/{user}', [UserController::class, 'show']);
    });

    Route::middleware('role:ADMIN,MANAGER')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update']);
    });

    Route::middleware('role:ADMIN')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});