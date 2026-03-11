<?php

namespace App\Dtos;

use App\Http\Requests\UpdateUserRequest;

readonly class UpdateUserDto
{
    public function __construct(
        public ?string $name     = null,
        public ?string $email    = null,
        public ?string $password = null,
        public ?string $role     = null,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:     $data['name']     ?? null,
            email:    $data['email']    ?? null,
            password: $data['password'] ?? null,
            role:     $data['role']     ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'role'     => $this->role,
        ], fn ($value) => $value !== null);
    }
}
