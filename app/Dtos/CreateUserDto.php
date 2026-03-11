<?php

namespace App\Dtos;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;

readonly class CreateUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = User::ROLE_USER,
    ) {}

    public static function fromRequest(StoreUserRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:     $data['name'],
            email:    $data['email'],
            password: $data['password'],
            role:     $data['role'] ?? User::ROLE_USER,
        );
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'role'     => $this->role,
        ];
    }
}
