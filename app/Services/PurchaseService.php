<?php

namespace App\Services;

use App\Dtos\Purchase\PurchaseDto;
use App\Dtos\Payment\ChargePayloadDto;
use App\Services\Payment\PaymentOrchestratorService;
use App\Services\Purchase\PurchaseAmountCalculatorService;
use App\Services\Purchase\PurchaseTransactionRecorderService;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly PaymentOrchestratorService $paymentOrchestrator,
        private readonly PurchaseAmountCalculatorService $purchaseAmountCalculator,
        private readonly PurchaseTransactionRecorderService $purchaseTransactionRecorder,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function purchase(PurchaseDto $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $client = $this->clientService->findOrCreate(
                $dto->clientName,
                $dto->clientEmail,
            );

            $calculatedPurchase = $this->purchaseAmountCalculator->calculate($dto->products);

            $payment = $this->paymentOrchestrator->charge(new ChargePayloadDto(
                amountInCents: (int) round($calculatedPurchase->amount * 100),
                name: $client->name,
                email: $client->email,
                cardNumber: $dto->cardNumber,
                cvv: $dto->cvv,
            ));

            $transaction = $this->purchaseTransactionRecorder->record(
                client: $client,
                gateway: $payment['gateway'] ?? null,
                externalId: $payment['external_id'] ?? null,
                amount: $calculatedPurchase->amount,
                cardNumber: $dto->cardNumber,
                paid: (bool) $payment['success'],
                products: $calculatedPurchase->products,
            );

            $transactionWithRelations = $this->transactionRepository->findWithRelations(
                $transaction->id,
                ['client', 'gateway', 'products'],
            ) ?? $transaction;

            if (!$payment['success']) {
                return [
                    'success' => false,
                    'transaction' => $transactionWithRelations,
                    'message' => 'Não foi possível processar a compra em nenhum gateway.',
                ];
            }

            return [
                'success' => true,
                'transaction' => $transactionWithRelations,
                'message' => 'Compra realizada com sucesso.',
            ];
        });
    }
}
