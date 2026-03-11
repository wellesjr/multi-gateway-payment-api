<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated('email'),
                $request->validated('password'),
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ]);
    }
}
