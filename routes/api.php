<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login',  [AuthController::class, 'login']);

Route::post('/comprar', function () {
    return response()->json(['message' => 'Compra realizada com sucesso']);
});
