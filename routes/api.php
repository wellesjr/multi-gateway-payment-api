<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/comprar', function () {
    return response()->json(['message' => 'Compra realizada com sucesso']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function () {
        return response()->json([
            'success' => true,
            'data'    => new UserResource(request()->user()),
        ]);
    });

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
