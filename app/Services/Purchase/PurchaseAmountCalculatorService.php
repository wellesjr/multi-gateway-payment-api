<?php

namespace App\Services\Purchase;

use App\Dtos\Purchase\CalculatedPurchaseDto;
use App\Repositories\Interfaces\ProductRepositoryInterface;

class PurchaseAmountCalculatorService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function calculate(array $products): CalculatedPurchaseDto
    {
        $availableProducts = $this->productRepository
            ->findManyByIds(collect($products)->pluck('id')->map(fn ($id): int => (int) $id)->all())
            ->keyBy('id');

        $amount = 0.0;
        $normalizedProducts = [];

        foreach ($products as $item) {
            $productId = (int) $item['id'];
            $quantity = (int) $item['quantity'];

            $product = $availableProducts->get($productId);

            if (!$product) {
                throw new \DomainException('Produto informado não foi encontrado.');
            }

            $amount += (float) $product->amount * $quantity;

            $normalizedProducts[] = [
                'id' => $productId,
                'quantity' => $quantity,
            ];
        }

        return new CalculatedPurchaseDto(
            amount: $amount,
            products: $normalizedProducts,
        );
    }
}
