<?php

namespace App\Dtos\Auth;

use App\Models\User;

readonly class LoginResultDto
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
