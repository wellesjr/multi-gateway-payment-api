<?php

namespace App\Dtos\Payment;

use App\Enums\PaymentAttemptStatus;
use Carbon\CarbonInterface;

readonly class PaymentAttemptDto
{
    public function __construct(
        public int $gatewayId,
        public string $gatewayName,
        public PaymentAttemptStatus $status,
        public ?string $externalId,
        public ?string $errorMessage,
        public CarbonInterface $attemptedAt,
    ) {}
}
