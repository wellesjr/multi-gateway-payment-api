<?php

namespace App\Services;

use App\Enums\PaymentAttemptStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\TransactionStatus;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class FinancialReconciliationService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function reconcile(int $transactionId): void
    {
        $transaction = $this->transactionRepository->findWithRelations(
            $transactionId,
            ['products', 'paymentAttempts', 'gateway'],
        );

        if (!$transaction) {
            return;
        }

        $issues = [];
        $expectedAmount = $transaction->products->sum(
            fn ($product) => ((float) $product->amount) * ((int) $product->pivot?->quantity)
        );
        $actualAmount = (float) $transaction->amount;

        if (abs($expectedAmount - $actualAmount) > 0.01) {
            $issues[] = 'Valor da transação divergente dos produtos.';
        }

        $hasSuccessAttempt = $transaction->paymentAttempts->contains(
            fn ($attempt) => $attempt->status === PaymentAttemptStatus::Success
        );

        switch ($transaction->status) {
            case TransactionStatus::Paid:
                $this->validatePaid($transaction->gateway_id, $transaction->external_id, $hasSuccessAttempt, $issues);
                break;
            case TransactionStatus::Failed:
                $this->validateFailed($hasSuccessAttempt, $issues);
                break;
            case TransactionStatus::Refunded:
                $this->validateRefunded($transaction->gateway_id, $transaction->external_id, $hasSuccessAttempt, $issues);
                break;
            case TransactionStatus::Pending:
                $issues[] = 'Transação pendente não pode ser reconciliada.';
                break;
        }

        if ($issues === []) {
            $this->transactionRepository->update($transaction, [
                'reconciliation_status' => ReconciliationStatus::Reconciled->value,
                'reconciled_at' => now(),
                'reconciliation_error' => null,
            ]);

            return;
        }

        $this->transactionRepository->update($transaction, [
            'reconciliation_status' => ReconciliationStatus::Failed->value,
            'reconciled_at' => null,
            'reconciliation_error' => implode(' ', $issues),
        ]);
    }

    /**
     * @param array<int, string> $issues
     */
    private function validatePaid(?int $gatewayId, ?string $externalId, bool $hasSuccessAttempt, array &$issues): void
    {
        if (!$gatewayId || !$externalId) {
            $issues[] = 'Transação paga sem gateway ou identificador externo.';
        }

        if (!$hasSuccessAttempt) {
            $issues[] = 'Transação paga sem tentativa de pagamento com sucesso.';
        }
    }

    /**
     * @param array<int, string> $issues
     */
    private function validateFailed(bool $hasSuccessAttempt, array &$issues): void
    {
        if ($hasSuccessAttempt) {
            $issues[] = 'Transação falha possui tentativa bem-sucedida.';
        }
    }

    /**
     * @param array<int, string> $issues
     */
    private function validateRefunded(?int $gatewayId, ?string $externalId, bool $hasSuccessAttempt, array &$issues): void
    {
        if (!$gatewayId || !$externalId) {
            $issues[] = 'Transação reembolsada sem gateway ou identificador externo.';
        }

        if (!$hasSuccessAttempt) {
            $issues[] = 'Transação reembolsada sem tentativa de pagamento original com sucesso.';
        }
    }
}
