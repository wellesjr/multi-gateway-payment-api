<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', function () {
    return response()->json(['message' => 'Login realizado com sucesso']);
});

Route::post('/comprar', function () {
    return response()->json(['message' => 'Compra realizada com sucesso']);
});
