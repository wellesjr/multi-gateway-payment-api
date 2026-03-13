<?php

namespace App\Gateways;

use App\Repositories\Interfaces\PaymentGatewayClientInterface;
use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Payment\GatewayChargeResultDto;

use Illuminate\Http\Client\Factory as HttpFactory;

class Gateway1Client implements PaymentGatewayClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function gatewayName(): string
    {
        return 'gateway1';
    }

    public function charge(ChargePayloadDto $payload): GatewayChargeResultDto
    {
        $token = $this->authenticate();

        $response = $this->http
            ->withToken($token)
            ->post(rtrim((string) config('services.gateway1.url'), '/') . '/transactions', [
                'amount' => $payload->amountInCents,
                'name' => $payload->name,
                'email' => $payload->email,
                'cardNumber' => $payload->cardNumber,
                'cvv' => $payload->cvv,
            ]);

        if ($response->failed()) {
            return GatewayChargeResultDto::failed('Gateway 1 retornou erro ao cobrar.');
        }

        $externalId = (string) $response->json('id');

        if ($externalId === '') {
            return GatewayChargeResultDto::failed('Gateway 1 sem id externo de transação.');
        }

        return GatewayChargeResultDto::success($externalId);
    }

    public function refund(string $externalId): bool
    {
        $token = $this->authenticate();

        $response = $this->http
            ->withToken($token)
            ->post(rtrim((string) config('services.gateway1.url'), '/') . '/transactions/' . $externalId . '/charge_back');

        return $response->successful();
    }

    private function authenticate(): string
    {
        $response = $this->http->post(rtrim((string) config('services.gateway1.url'), '/') . '/login', [
            'email' => config('services.gateway1.email'),
            'token' => config('services.gateway1.token'),
        ]);

        if ($response->failed()) {
            throw new \DomainException('Falha ao autenticar no Gateway 1.');
        }

        $token = (string) $response->json('token');

        if ($token === '') {
            throw new \DomainException('Token inválido recebido do Gateway 1.');
        }

        return $token;
    }
}
