<?php

namespace App\Services;

use App\Dtos\Auth\LoginResultDto;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(string $email, string $password): LoginResultDto
    {
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new \DomainException('Credenciais inválidas');
        }

        $user  = Auth::user();
        if (!$user) {
            throw new \DomainException('Não foi possível recuperar o usuário autenticado.');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return new LoginResultDto(
            user: $user,
            token: $token,
        );
    }
}
