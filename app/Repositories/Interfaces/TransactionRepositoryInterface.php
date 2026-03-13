<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    public function findWithRelations(int $id, array $relations = []): ?Transaction;

    public function create(array $data): Transaction;

    public function attachProducts(Transaction $transaction, array $products): void;

    public function update(Transaction $transaction, array $data): Transaction;
}
