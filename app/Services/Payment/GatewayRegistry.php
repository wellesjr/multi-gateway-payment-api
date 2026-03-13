<?php

namespace App\Services\Payment;

use App\Repositories\Interfaces\PaymentGatewayClientInterface;

class GatewayRegistry
{
    private array $clientsByGateway = [];

    public function __construct(iterable $clients)
    {
        foreach ($clients as $client) {
            $gatewayName = strtolower($client->gatewayName());

            if (isset($this->clientsByGateway[$gatewayName])) {
                throw new \LogicException('Cliente de gateway duplicado registrado para: ' . $gatewayName);
            }

            $this->clientsByGateway[$gatewayName] = $client;
        }
    }

    public function get(string $gatewayName): PaymentGatewayClientInterface
    {
        $normalizedGatewayName = strtolower($gatewayName);

        if (!isset($this->clientsByGateway[$normalizedGatewayName])) {
            throw new \DomainException('Gateway não suportado: ' . $gatewayName);
        }

        return $this->clientsByGateway[$normalizedGatewayName];
    }
}
