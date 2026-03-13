<?php

namespace App\Services;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    public function __construct(
        private readonly PaymentOrchestratorService $paymentOrchestrator,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->paginateWithRelations($perPage, ['client', 'gateway']);
    }

    public function findById(int $id): ?Transaction
    {
        return $this->transactionRepository->findWithRelations($id, ['client', 'gateway', 'products']);
    }

    public function refund(Transaction $transaction): Transaction
    {
        $transactionWithGateway = $this->transactionRepository->findWithRelations($transaction->id, ['gateway']);

        if (!$transactionWithGateway) {
            throw new \DomainException('Transação não encontrada.');
        }

        if ($transactionWithGateway->status !== 'paid') {
            throw new \DomainException('Somente transações pagas podem ser reembolsadas.');
        }

        $refunded = $this->paymentOrchestrator->refund($transactionWithGateway);

        if (!$refunded) {
            throw new \DomainException('O gateway não autorizou o reembolso.');
        }

        $updatedTransaction = $this->transactionRepository->update($transactionWithGateway, ['status' => 'refunded']);

        return $this->transactionRepository->findWithRelations($updatedTransaction->id, ['client', 'gateway', 'products'])
            ?? $updatedTransaction;
    }
}
