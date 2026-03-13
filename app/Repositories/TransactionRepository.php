<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        private readonly Transaction $model,
    ) {}

    public function paginateWithRelations(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with($relations)
            ->latest()
            ->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = []): ?Transaction
    {
        return $this->model->newQuery()
            ->with($relations)
            ->find($id);
    }

    public function create(array $data): Transaction
    {
        return $this->model->newQuery()->create($data);
    }

    public function attachProducts(Transaction $transaction, array $products): void
    {
        foreach ($products as $item) {
            $transaction->products()->attach(
                $item['id'],
                ['quantity' => $item['quantity']],
            );
        }
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->fill($data);
        $transaction->updated_at = now();
        $transaction->save();

        return $transaction->fresh();
    }
}
