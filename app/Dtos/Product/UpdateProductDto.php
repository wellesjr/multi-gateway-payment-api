<?php

namespace App\Dtos\Product;

use App\Http\Requests\Product\UpdateProductRequest;

readonly class UpdateProductDto
{
    public function __construct(
        public ?string $name   = null,
        public ?float  $amount = null,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name:   $data['name']   ?? null,
            amount: $data['amount'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'   => $this->name,
            'amount' => $this->amount,
        ], fn ($value) => $value !== null);
    }
}
