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

        $amountInCents = 0;
        $normalizedProducts = [];

        foreach ($products as $item) {
            $productId = (int) $item['id'];
            $quantity = (int) $item['quantity'];

            $product = $availableProducts->get($productId);

            if (!$product) {
                throw new \DomainException('Produto informado não foi encontrado.');
            }

            $amountInCents += $this->decimalToCents((string) $product->amount) * $quantity;

            $normalizedProducts[] = [
                'id' => $productId,
                'quantity' => $quantity,
            ];
        }

        return new CalculatedPurchaseDto(
            amount: $amountInCents / 100,
            amountInCents: $amountInCents,
            products: $normalizedProducts,
        );
    }

    private function decimalToCents(string $amount): int
    {
        $normalized = str_replace(',', '.', trim($amount));

        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \DomainException('Valor do produto inválido para cálculo de compra.');
        }

        [$integerPart, $fractionPart] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fractionPart = str_pad(substr($fractionPart, 0, 2), 2, '0');

        return ((int) $integerPart * 100) + (int) $fractionPart;
    }
}
