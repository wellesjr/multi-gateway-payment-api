<?php

namespace App\Dtos\Product;

use App\Http\Requests\Product\StoreProductRequest;

readonly class CreateProductDto
{
    public function __construct(
        public string $name,
        public float  $amount,
    ) {}

    public static function fromRequest(StoreProductRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:   $data['name'],
            amount: $data['amount'],
        );
    }

    public function toArray(): array
    {
        return [
            'name'   => $this->name,
            'amount' => $this->amount,
        ];
    }
}
