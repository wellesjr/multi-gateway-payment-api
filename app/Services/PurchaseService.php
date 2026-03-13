<?php

namespace App\Services;

use App\Dtos\Purchase\PurchaseDto;
use App\Dtos\Purchase\PurchaseResultDto;
use App\Enums\IdempotencyScope;
use App\Enums\TransactionStatus;
use App\Jobs\ReconcileFinancialTransactionJob;
use App\Repositories\Interfaces\IdempotencyKeyRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\UseCases\Purchase\FinalizePurchaseUseCase;
use App\UseCases\Purchase\ResolvePurchasePaymentUseCase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly IdempotencyKeyRepositoryInterface $idempotencyKeyRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ResolvePurchasePaymentUseCase $resolvePurchasePaymentUseCase,
        private readonly FinalizePurchaseUseCase $finalizePurchaseUseCase,
    ) {}

    public function purchase(PurchaseDto $dto, ?string $idempotencyKey = null): PurchaseResultDto
    {
        return DB::transaction(function () use ($dto, $idempotencyKey) {
            $idempotencyRecord = null;

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                if (mb_strlen($idempotencyKey) > 255) {
                    throw new \DomainException('Idempotency-Key deve possuir no máximo 255 caracteres.');
                }

                $idempotencyRecord = $this->idempotencyKeyRepository->findByScopeAndKeyForUpdate(
                    IdempotencyScope::Purchase->value,
                    $idempotencyKey,
                );

                $requestFingerprint = $dto->fingerprint();

                if ($idempotencyRecord) {
                    if ($idempotencyRecord->request_fingerprint !== $requestFingerprint) {
                        throw new \DomainException('Idempotency-Key já utilizado com payload diferente.');
                    }

                    if ($idempotencyRecord->transaction_id) {
                        return $this->buildExistingPurchaseResult($idempotencyRecord->transaction_id);
                    }
                } else {
                    $idempotencyRecord = $this->idempotencyKeyRepository->create(
                        scope: IdempotencyScope::Purchase->value,
                        idempotencyKey: $idempotencyKey,
                        requestFingerprint: $requestFingerprint,
                    );
                }
            }

            $client = $this->clientService->findOrCreate(
                $dto->clientName,
                $dto->clientEmail,
            );

            $resolvedPayment = $this->resolvePurchasePaymentUseCase->execute($dto, $client);

            $result = $this->finalizePurchaseUseCase->execute(
                client: $client,
                dto: $dto,
                calculatedPurchase: $resolvedPayment->calculatedPurchase,
                payment: $resolvedPayment->payment,
            );

            if ($idempotencyRecord && !$idempotencyRecord->transaction_id) {
                $this->idempotencyKeyRepository->attachTransaction($idempotencyRecord, $result->transaction->id);
            }

            ReconcileFinancialTransactionJob::dispatch($result->transaction->id)->afterCommit();

            return $result;
        });
    }

    private function buildExistingPurchaseResult(int $transactionId): PurchaseResultDto
    {
        $transaction = $this->transactionRepository->findWithRelations(
            $transactionId,
            ['client', 'gateway', 'products', 'paymentAttempts.gateway'],
        );

        if (!$transaction) {
            throw new \DomainException('Transação idempotente não encontrada.');
        }

        if ($transaction->status === TransactionStatus::Failed) {
            return new PurchaseResultDto(
                success: false,
                transaction: $transaction,
                message: 'Não foi possível processar a compra em nenhum gateway.',
            );
        }

        return new PurchaseResultDto(
            success: true,
            transaction: $transaction,
            message: 'Compra já processada anteriormente.',
        );
    }
}
