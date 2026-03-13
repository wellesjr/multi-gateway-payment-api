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

    public function toArray(): array
    {
        return [
            'client' => [
                'name' => $this->clientName,
                'email' => $this->clientEmail,
            ],
            'products' => $this->products,
            'card_number' => $this->cardNumber,
            'cvv' => $this->cvv,
        ];
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }
}
