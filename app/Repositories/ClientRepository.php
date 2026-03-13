<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly Client $model,
    ) {}

    public function findByEmail(string $email): ?Client
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function create(array $data): Client
    {
        return $this->model->newQuery()->create($data);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function findById(int $id): ?Client
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id, array $relations = []): ?Client
    {
        return $this->model->newQuery()->with($relations)->find($id);
    }
}