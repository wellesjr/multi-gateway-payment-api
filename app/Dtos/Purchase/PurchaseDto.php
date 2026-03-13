<?php

namespace App\Dtos\Purchase;

class PurchaseDto
{
    public function __construct(
        public string $clientName,
        public string $clientEmail,
        public array $products,
        public string $cardNumber,
        public string $cvv,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['client']['name'],
            $data['client']['email'],
            $data['products'],
            $data['card_number'],
            $data['cvv'],
        );
    }
}
