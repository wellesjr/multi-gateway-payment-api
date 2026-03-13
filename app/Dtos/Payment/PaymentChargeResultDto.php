<?php

namespace App\Dtos\Payment;

use App\Models\Gateway;

readonly class PaymentChargeResultDto
{
    public function __construct(
        public bool $success,
        public ?Gateway $gateway,
        public ?string $externalId,
        public array $errors,
        public array $attempts,
    ) {}

    public static function success(Gateway $gateway, string $externalId, array $attempts): self
    {
        return new self(
            success: true,
            gateway: $gateway,
            externalId: $externalId,
            errors: [],
            attempts: $attempts,
        );
    }
    public static function failed(array $errors, array $attempts): self
    {
        return new self(
            success: false,
            gateway: null,
            externalId: null,
            errors: $errors,
            attempts: $attempts,
        );
    }
}