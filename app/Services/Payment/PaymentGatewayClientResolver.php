<?php

namespace App\Services\Payment;

use App\Repositories\Interfaces\PaymentGatewayClientInterface;

class PaymentGatewayClientResolver
{
    public function __construct(
        private readonly GatewayRegistry $gatewayRegistry,
    ) {}

    public function resolve(string $gatewayName): PaymentGatewayClientInterface
    {
        return $this->gatewayRegistry->get($gatewayName);
    }
}
