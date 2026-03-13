<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/comprar', [PurchaseController::class, 'store'])->middleware('throttle:api');

require __DIR__ . '/api/v1/user.php';
require __DIR__ . '/api/v1/clients.php';
require __DIR__ . '/api/v1/products.php';
require __DIR__ . '/api/v1/gateways.php';
require __DIR__ . '/api/v1/transactions.php';
