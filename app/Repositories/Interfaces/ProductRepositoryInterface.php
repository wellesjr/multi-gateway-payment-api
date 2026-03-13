<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findManyByIds(array $ids): Collection;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;
}
