<?php

namespace App\Services;

use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Models\Client;

class ClientService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository
    ) {}

    public function findOrCreate(string $name, string $email): Client
    {
        $client = $this->clientRepository->findByEmail($email);

        if ($client) {
            return $client;
        }

        return $this->clientRepository->create([
            'name' => $name,
            'email' => $email
        ]);
    }

    public function list()
    {
        return $this->clientRepository->paginate();
    }

    public function find(int $id)
    {
        return $this->clientRepository->findById($id);
    }
}