<?php

namespace App\Services;

use App\Enums\IdempotencyScope;
use App\Enums\TransactionStatus;
use App\Jobs\ReconcileFinancialTransactionJob;
use App\Models\Transaction;
use App\Repositories\Interfaces\IdempotencyKeyRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly PaymentOrchestratorService $paymentOrchestrator,
        private readonly IdempotencyKeyRepositoryInterface $idempotencyKeyRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->paginateWithRelations($perPage, ['client', 'gateway']);
    }

    public function findById(int $id): ?Transaction
    {
        return $this->transactionRepository->findWithRelations($id, ['client', 'gateway', 'products', 'paymentAttempts.gateway']);
    }

    public function refund(Transaction $transaction, ?string $idempotencyKey = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $idempotencyKey) {
            $idempotencyRecord = null;

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                if (mb_strlen($idempotencyKey) > 255) {
                    throw new \DomainException('Idempotency-Key deve possuir no máximo 255 caracteres.');
                }

                $idempotencyRecord = $this->idempotencyKeyRepository->findByScopeAndKeyForUpdate(
                    IdempotencyScope::Refund->value,
                    $idempotencyKey,
                );

                $requestFingerprint = hash('sha256', "refund:{$transaction->id}");

                if ($idempotencyRecord) {
                    if ($idempotencyRecord->request_fingerprint !== $requestFingerprint) {
                        throw new \DomainException('Idempotency-Key já utilizado com outra transação de reembolso.');
                    }

                    if ($idempotencyRecord->transaction_id) {
                        $existingTransaction = $this->transactionRepository->findWithRelations(
                            $idempotencyRecord->transaction_id,
                            ['client', 'gateway', 'products', 'paymentAttempts.gateway'],
                        );

                        if (!$existingTransaction) {
                            throw new \DomainException('Transação idempotente de reembolso não encontrada.');
                        }

                        return $existingTransaction;
                    }
                } else {
                    $idempotencyRecord = $this->idempotencyKeyRepository->create(
                        scope: IdempotencyScope::Refund->value,
                        idempotencyKey: $idempotencyKey,
                        requestFingerprint: $requestFingerprint,
                    );
                }
            }

            $transactionWithGateway = $this->transactionRepository->findWithRelations($transaction->id, ['gateway']);

            if (!$transactionWithGateway) {
                throw new \DomainException('Transação não encontrada.');
            }

            if ($transactionWithGateway->status !== TransactionStatus::Paid) {
                throw new \DomainException('Somente transações pagas podem ser reembolsadas.');
            }

            $refunded = $this->paymentOrchestrator->refund($transactionWithGateway);

            if (!$refunded) {
                throw new \DomainException('O gateway não autorizou o reembolso.');
            }

            $updatedTransaction = $this->transactionRepository->update($transactionWithGateway, [
                'status' => TransactionStatus::Refunded->value,
            ]);

            if ($idempotencyRecord && !$idempotencyRecord->transaction_id) {
                $this->idempotencyKeyRepository->attachTransaction($idempotencyRecord, $updatedTransaction->id);
            }

            ReconcileFinancialTransactionJob::dispatch($updatedTransaction->id)->afterCommit();

            return $this->transactionRepository->findWithRelations($updatedTransaction->id, ['client', 'gateway', 'products', 'paymentAttempts.gateway'])
                ?? $updatedTransaction;
        });
    }
}
