<?php

namespace App\Gateways;

use App\Repositories\Interfaces\PaymentGatewayClientInterface;
use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Payment\GatewayChargeResultDto;

use Illuminate\Http\Client\Factory as HttpFactory;

class Gateway2Client implements PaymentGatewayClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function gatewayName(): string
    {
        return 'gateway2';
    }

    public function charge(ChargePayloadDto $payload): GatewayChargeResultDto
    {
        $response = $this->client()->post(rtrim((string) config('services.gateway2.url'), '/') . '/transacoes', [
            'valor' => $payload->amountInCents,
            'nome' => $payload->name,
            'email' => $payload->email,
            'numeroCartao' => $payload->cardNumber,
            'cvv' => $payload->cvv,
        ]);

        if ($response->failed()) {
            return GatewayChargeResultDto::failed('Gateway 2 retornou erro ao cobrar.');
        }

        $externalId = (string) $response->json('id');

        if ($externalId === '') {
            return GatewayChargeResultDto::failed('Gateway 2 sem id externo de transação.');
        }

        return GatewayChargeResultDto::success($externalId);
    }

    public function refund(string $externalId): bool
    {
        $response = $this->client()->post(rtrim((string) config('services.gateway2.url'), '/') . '/transacoes/reembolso', [
            'id' => $externalId,
        ]);

        return $response->successful();
    }

    private function client()
    {
        return $this->http
            ->withHeaders([
                'Gateway-Auth-Token' => (string) config('services.gateway2.auth_token'),
                'Gateway-Auth-Secret' => (string) config('services.gateway2.auth_secret'),
            ]);
    }
}