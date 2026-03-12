<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:ADMIN,MANAGER,FINANCE'])->group(function () {
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{id}', [ClientController::class, 'show']);
});