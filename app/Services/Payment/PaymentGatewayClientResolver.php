<?php

namespace App\Services\Payment;

use App\Gateways\Gateway1Client;
use App\Gateways\Gateway2Client;
use App\Repositories\Interfaces\PaymentGatewayClientInterface;

class PaymentGatewayClientResolver
{
    public function resolve(string $gatewayName): PaymentGatewayClientInterface
    {
        return match (strtolower($gatewayName)) {
            'gateway1' => app(Gateway1Client::class),
            'gateway2' => app(Gateway2Client::class),
            default => throw new \DomainException('Gateway não suportado: ' . $gatewayName),
        };
    }
}
