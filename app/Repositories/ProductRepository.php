<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly Product $model,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->get();
    }

    public function create(array $data): Product
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill($data);
        $product->updated_at = now();
        $product->save();

        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
