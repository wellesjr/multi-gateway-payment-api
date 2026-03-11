<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * @return array{user: \App\Models\User, token: string}
     *
     * @throws \DomainException
     */
    public function login(string $email, string $password): array
    {
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new \DomainException('Credenciais inválidas');
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    }
}
