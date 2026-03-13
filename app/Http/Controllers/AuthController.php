<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Support\ApiResponse;
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
            return ApiResponse::error($e->getMessage(), 401);
        }

        return ApiResponse::success(
            message: 'Login realizado com sucesso',
            extra: [
                'user' => $result->user,
                'token' => $result->token,
            ],
        );
    }
}
