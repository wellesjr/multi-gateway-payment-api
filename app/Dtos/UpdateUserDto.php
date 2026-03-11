<?php

namespace App\Dtos;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRequest;

readonly class UpdateUserDto
{
    public function __construct(
        public ?string   $name     = null,
        public ?string   $email    = null,
        public ?string   $password = null,
        public ?UserRole $role     = null,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:     $data['name']     ?? null,
            email:    $data['email']    ?? null,
            password: $data['password'] ?? null,
            role:     isset($data['role']) ? UserRole::from($data['role']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'role'     => $this->role?->value,
        ], fn ($value) => $value !== null);
    }
}
