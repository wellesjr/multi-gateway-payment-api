<?php

namespace App\Services;

use App\Models\Product;
use App\Dtos\Product\CreateProductDto;
use App\Dtos\Product\UpdateProductDto;
use App\Repositories\Interfaces\ProductRepositoryInterface;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    public function create(CreateProductDto $dto): Product
    {
        return $this->productRepository->create($dto->toArray());
    }

    public function update(Product $product, UpdateProductDto $dto): Product
    {
        return $this->productRepository->update($product, $dto->toArray());
    }

    public function delete(Product $product): void
    {
        $this->productRepository->delete($product);
    }
}
