<?php

namespace App\Repositories\Interfaces;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClientRepositoryInterface
{
    public function findByEmail(string $email): ?Client;

    public function create(array $data): Client;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Client;
}