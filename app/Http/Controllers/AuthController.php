<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $user = Auth::user();

        return response()->json(['message' => 'Login realizado com sucesso', 'user' => $user]);
    }
}
