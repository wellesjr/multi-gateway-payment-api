<?php

namespace App\Dtos;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;

readonly class CreateUserDto
{
    public function __construct(
        public string   $name,
        public string   $email,
        public string   $password,
        public UserRole $role = UserRole::User,
    ) {}

    public static function fromRequest(StoreUserRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:     $data['name'],
            email:    $data['email'],
            password: $data['password'],
            role:     UserRole::tryFrom($data['role'] ?? '') ?? UserRole::User,
        );
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'role'     => $this->role->value,
        ];
    }
}
