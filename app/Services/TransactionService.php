<?php

namespace App\Services;

use App\Models\Transaction;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    public function __construct(
        private readonly PaymentOrchestratorService $paymentOrchestrator,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::query()
            ->with(['client', 'gateway'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::query()
            ->with(['client', 'gateway', 'products'])
            ->find($id);
    }

    public function refund(Transaction $transaction): Transaction
    {
        if ($transaction->status !== 'paid') {
            throw new \DomainException('Somente transações pagas podem ser reembolsadas.');
        }

        $refunded = $this->paymentOrchestrator->refund($transaction->load('gateway'));

        if (!$refunded) {
            throw new \DomainException('O gateway não autorizou o reembolso.');
        }

        $transaction->update(['status' => 'refunded']);

        return $transaction->fresh(['client', 'gateway', 'products']);
    }
}
