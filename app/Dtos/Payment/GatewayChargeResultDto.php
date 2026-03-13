<?php

namespace App\Dtos\Payment;

class GatewayChargeResultDto
{
    public function __construct(
        public bool $success,
        public ?string $externalId = null,
        public ?string $error = null,
    ) {}

    public static function success(string $externalId): self
    {
        return new self(true, $externalId, null);
    }

    public static function failed(string $error): self
    {
        return new self(false, null, $error);
    }
}
