<?php

namespace App\Dtos\Payment;

class ChargePayloadDto
{
    public function __construct(
        public int $amountInCents,
        public string $name,
        public string $email,
        public string $cardNumber,
        public string $cvv,
    ) {}
}
