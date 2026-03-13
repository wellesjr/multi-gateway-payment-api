<?php

namespace App\Services\Payment;

use App\Models\Gateway;
use App\Models\Transaction;
use App\Dtos\Payment\ChargePayloadDto;

class PaymentOrchestratorService
{
    public function __construct(
        private readonly PaymentGatewayClientResolver $gatewayClientResolver,
    ) {}

    /**
     * @return array{success: bool, gateway: ?Gateway, external_id: ?string, errors: array<int, string>}
     */
    public function charge(ChargePayloadDto $payload): array
    {
        $gateways = Gateway::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        if ($gateways->isEmpty()) {
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

                if ($result->success) {
                    return [
                        'success' => true,
                        'gateway' => $gateway,
                        'external_id' => $result->externalId,
                        'errors' => [],
                    ];
                }

                $errors[] = $result->error ?? "Falha ao processar no gateway {$gateway->name}.";
            } catch (\Throwable $e) {
                $errors[] = "Falha ao processar no gateway {$gateway->name}.";
            }
        }

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
