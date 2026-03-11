<?php

namespace App\Services;

use App\Models\User;
use App\Dtos\CreateUserDto;
use App\Dtos\UpdateUserDto;
use App\Repositories\Interfaces\UserRepositoryInterface;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    public function create(CreateUserDto $dto): User
    {
        return $this->userRepository->create($dto->toArray());
    }

    public function update(User $user, UpdateUserDto $dto): User
    {
        return $this->userRepository->update($user, $dto->toArray());
    }

    public function delete(User $user, User $authUser): void
    {
        if ($authUser->id === $user->id) {
            throw new \DomainException('Você não pode excluir sua própria conta.');
        }

        $this->userRepository->delete($user);
    }
}
