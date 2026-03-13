<?php

namespace App\UseCases\Purchase;

use App\Dtos\Payment\PaymentChargeResultDto;
use App\Dtos\Purchase\CalculatedPurchaseDto;
use App\Dtos\Purchase\PurchaseDto;
use App\Dtos\Purchase\PurchaseResultDto;
use App\Models\Client;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Purchase\PurchaseTransactionRecorderService;

class FinalizePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseTransactionRecorderService $purchaseTransactionRecorder,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function execute(
        Client $client,
        PurchaseDto $dto,
        CalculatedPurchaseDto $calculatedPurchase,
        PaymentChargeResultDto $payment,
    ): PurchaseResultDto {
        $transaction = $this->purchaseTransactionRecorder->record(
            client: $client,
            gateway: $payment->gateway,
            externalId: $payment->externalId,
            amount: $calculatedPurchase->amount,
            cardNumber: $dto->cardNumber,
            paid: $payment->success,
            products: $calculatedPurchase->products,
            attempts: $payment->attempts,
        );

        $transactionWithRelations = $this->transactionRepository->findWithRelations(
            $transaction->id,
            ['client', 'gateway', 'products', 'paymentAttempts.gateway'],
        ) ?? $transaction;

        if (!$payment->success) {
            return new PurchaseResultDto(
                success: false,
                transaction: $transactionWithRelations,
                message: 'Não foi possível processar a compra em nenhum gateway.',
            );
        }

        return new PurchaseResultDto(
            success: true,
            transaction: $transactionWithRelations,
            message: 'Compra realizada com sucesso.',
        );
    }
}