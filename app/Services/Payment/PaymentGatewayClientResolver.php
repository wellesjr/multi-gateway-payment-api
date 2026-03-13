<?php

namespace App\Services\Payment;

use App\Repositories\Interfaces\PaymentGatewayClientInterface;

class PaymentGatewayClientResolver
{
    /**
     * @var array<string, PaymentGatewayClientInterface>
     */
    private array $clientsByGateway = [];

    /**
     * @param iterable<int, PaymentGatewayClientInterface> $clients
     */
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

    public function resolve(string $gatewayName): PaymentGatewayClientInterface
    {
        $normalizedGatewayName = strtolower($gatewayName);

        if (!isset($this->clientsByGateway[$normalizedGatewayName])) {
            throw new \DomainException('Gateway não suportado: ' . $gatewayName);
        }

        return $this->clientsByGateway[$normalizedGatewayName];
    }
}
