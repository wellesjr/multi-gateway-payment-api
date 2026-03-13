<?php

namespace App\Services\Payment;

use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Payment\PaymentAttemptDto;
use App\Dtos\Payment\PaymentChargeResultDto;
use App\Enums\PaymentAttemptStatus;
use App\Models\Transaction;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class PaymentOrchestratorService
{
    public function __construct(
        private readonly PaymentGatewayClientResolver $gatewayClientResolver,
        private readonly GatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function charge(ChargePayloadDto $payload): PaymentChargeResultDto
    {
        $gateways = $this->gatewayRepository->activeOrderedByPriority();

        if ($gateways->isEmpty()) {
            Log::warning('payment.gateway.none_active');

            return PaymentChargeResultDto::failed(
                errors: ['Nenhum gateway ativo encontrado.'],
                attempts: [],
            );
        }

        $errors = [];
        $attempts = [];

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
                $attempts[] = new PaymentAttemptDto(
                    gatewayId: $gateway->id,
                    gatewayName: $gateway->name,
                    status: PaymentAttemptStatus::Exception,
                    externalId: null,
                    errorMessage: $exception->getMessage(),
                    attemptedAt: now(),
                );

                continue;
            }

            if ($result->success) {
                $attempts[] = new PaymentAttemptDto(
                    gatewayId: $gateway->id,
                    gatewayName: $gateway->name,
                    status: PaymentAttemptStatus::Success,
                    externalId: $result->externalId,
                    errorMessage: null,
                    attemptedAt: now(),
                );

                return PaymentChargeResultDto::success(
                    gateway: $gateway,
                    externalId: $result->externalId,
                    attempts: $attempts,
                );
            }

            $errorMessage = $result->error ?? "Falha ao processar no gateway {$gateway->name}.";

            Log::warning('payment.gateway.charge.failed', [
                'gateway_id' => $gateway->id,
                'gateway_name' => $gateway->name,
                'gateway_error' => $result->error,
            ]);

            $errors[] = $errorMessage;
            $attempts[] = new PaymentAttemptDto(
                gatewayId: $gateway->id,
                gatewayName: $gateway->name,
                status: PaymentAttemptStatus::Failed,
                externalId: null,
                errorMessage: $errorMessage,
                attemptedAt: now(),
            );
        }

        Log::warning('payment.gateway.charge.all_failed', [
            'attempted_gateways' => $gateways->pluck('name')->values()->all(),
            'errors' => $errors,
        ]);

        return PaymentChargeResultDto::failed(
            errors: $errors,
            attempts: $attempts,
        );
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