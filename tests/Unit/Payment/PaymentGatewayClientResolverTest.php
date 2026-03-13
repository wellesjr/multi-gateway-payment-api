<?php

namespace Tests\Unit\Payment;

use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Payment\GatewayChargeResultDto;
use App\Repositories\Interfaces\PaymentGatewayClientInterface;
use App\Services\Payment\GatewayRegistry;
use App\Services\Payment\PaymentGatewayClientResolver;
use PHPUnit\Framework\TestCase;

class PaymentGatewayClientResolverTest extends TestCase
{
    public function test_it_resolves_registered_gateway_client(): void
    {
        $gateway1Client = $this->makeClient('gateway1');
        $gateway2Client = $this->makeClient('gateway2');

        $resolver = new PaymentGatewayClientResolver(new GatewayRegistry([
            $gateway1Client,
            $gateway2Client,
        ]));

        $resolvedClient = $resolver->resolve('gateway2');

        $this->assertSame($gateway2Client, $resolvedClient);
    }

    public function test_it_throws_exception_for_unknown_gateway(): void
    {
        $resolver = new PaymentGatewayClientResolver(new GatewayRegistry([
            $this->makeClient('gateway1'),
        ]));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Gateway não suportado: gateway3');

        $resolver->resolve('gateway3');
    }

    public function test_it_throws_exception_for_duplicate_gateway_registration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cliente de gateway duplicado registrado para: gateway1');

        new PaymentGatewayClientResolver(new GatewayRegistry([
            $this->makeClient('gateway1'),
            $this->makeClient('gateway1'),
        ]));
    }

    private function makeClient(string $gatewayName): PaymentGatewayClientInterface
    {
        return new class($gatewayName) implements PaymentGatewayClientInterface {
            public function __construct(
                private readonly string $gatewayName,
            ) {}

            public function gatewayName(): string
            {
                return $this->gatewayName;
            }

            public function charge(ChargePayloadDto $payload): GatewayChargeResultDto
            {
                return GatewayChargeResultDto::failed('unused in resolver tests');
            }

            public function refund(string $externalId): bool
            {
                return false;
            }
        };
    }
}
