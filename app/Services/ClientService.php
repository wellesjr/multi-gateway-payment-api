<?php

namespace App\Services;

use App\Models\Client;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientService
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository
    ) {}

    public function findOrCreate(string $name, string $email): Client
    {
        $client = $this->clientRepository->findByEmail($email);

        if ($client) {
            return $client;
        }

        return $this->clientRepository->create([
            'name'  => $name,
            'email' => $email,
        ]);
    }

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->clientRepository->paginate($perPage);
    }

    public function find(int $id): ?Client
    {
        return $this->clientRepository->findById($id);
    }
}