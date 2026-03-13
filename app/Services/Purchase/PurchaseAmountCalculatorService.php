<?php

namespace App\Services\Purchase;

use App\Dtos\Purchase\CalculatedPurchaseDto;
use App\Models\Product;

class PurchaseAmountCalculatorService
{
    /**
     * @param array<int, array{id: int, quantity: int}> $products
     *
     * @throws \DomainException
     */
    public function calculate(array $products): CalculatedPurchaseDto
    {
        $availableProducts = Product::query()
            ->whereIn('id', collect($products)->pluck('id'))
            ->get()
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
