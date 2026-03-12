<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Interfaces\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function findByEmail(string $email): ?Client
    {
        return Client::where('email', $email)->first();
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function paginate()
    {
        return Client::paginate();
    }

    public function findById(int $id): ?Client
    {
        return Client::find($id);
    }
}