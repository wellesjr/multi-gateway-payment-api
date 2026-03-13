<?php

namespace App\Dtos\Purchase;

readonly class CalculatedPurchaseDto
{
    /**
     * @param array<int, array{id: int, quantity: int}> $products
     */
    public function __construct(
        public float $amount,
        public array $products,
    ) {}
}
