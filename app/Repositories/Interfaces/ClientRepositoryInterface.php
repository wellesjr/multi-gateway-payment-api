<?php

namespace App\Repositories\Interfaces;

use App\Models\Client;

interface ClientRepositoryInterface
{
    public function findByEmail(string $email): ?Client;

    public function create(array $data): Client;

    public function paginate();

    public function findById(int $id): ?Client;
}