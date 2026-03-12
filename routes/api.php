<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/comprar', function () {
    return response()->json(['message' => 'Compra realizada com sucesso']);
});

require __DIR__.'/api/v1/user.php';
require __DIR__.'/api/v1/clients.php';
require __DIR__.'/api/v1/products.php';