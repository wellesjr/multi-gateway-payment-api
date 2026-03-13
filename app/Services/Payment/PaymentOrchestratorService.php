<?php

namespace App\Services\Payment;

use App\Models\Gateway;
use App\Models\Transaction;
use App\Dtos\Payment\ChargePayloadDto;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class PaymentOrchestratorService
{
    public function __construct(
        private readonly PaymentGatewayClientResolver $gatewayClientResolver,
        private readonly GatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function charge(ChargePayloadDto $payload): array
    {
        $gateways = $this->gatewayRepository->activeOrderedByPriority();

        if ($gateways->isEmpty()) {
            Log::warning('payment.gateway.none_active');

            return [
                'success' => false,
                'gateway' => null,
                'external_id' => null,
                'errors' => ['Nenhum gateway ativo encontrado.'],
            ];
        }

        $errors = [];

        foreach ($gateways as $gateway) {
            try {
                $client = $this->gatewayClientResolver->resolve($gateway->name);
                $result = $client->charge($payload);
            } catch (\DomainException|ConnectionException $exception) {
                $errorMessage = "Falha ao processar no gateway {$gateway->name}.";

                Log::warning('payment.gateway.charge.exception', [
                    'gateway_id' => $gateway->id,
                    'gateway_name' => $gateway->name,
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]);

                $errors[] = $errorMessage;

                continue;
            }

            if ($result->success) {
                return [
                    'success' => true,
                    'gateway' => $gateway,
                    'external_id' => $result->externalId,
                    'errors' => [],
                ];
            }

            $errorMessage = $result->error ?? "Falha ao processar no gateway {$gateway->name}.";

            Log::warning('payment.gateway.charge.failed', [
                'gateway_id' => $gateway->id,
                'gateway_name' => $gateway->name,
                'gateway_error' => $result->error,
            ]);

            $errors[] = $errorMessage;
        }

        Log::warning('payment.gateway.charge.all_failed', [
            'attempted_gateways' => $gateways->pluck('name')->values()->all(),
            'errors' => $errors,
        ]);

        return [
            'success' => false,
            'gateway' => null,
            'external_id' => null,
            'errors' => $errors,
        ];
    }

    public function refund(Transaction $transaction): bool
    {
        if (!$transaction->gateway || !$transaction->external_id) {
            throw new \DomainException('Transação sem gateway ou id externo para reembolso.');
        }

        $client = $this->gatewayClientResolver->resolve($transaction->gateway->name);

        return $client->refund($transaction->external_id);
    }
}
