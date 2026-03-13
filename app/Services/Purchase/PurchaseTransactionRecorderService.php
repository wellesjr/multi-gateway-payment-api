<?php

namespace App\Services\Purchase;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Repositories\Interfaces\PaymentAttemptRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class PurchaseTransactionRecorderService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly PaymentAttemptRepositoryInterface $paymentAttemptRepository,
    ) {}

    public function record(
        Client $client,
        ?Gateway $gateway,
        ?string $externalId,
        float $amount,
        string $cardNumber,
        bool $paid,
        array $products,
        array $attempts = [],
    ): Transaction {
        $transaction = $this->transactionRepository->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway?->id,
            'external_id' => $externalId,
            'status' => $paid ? 'paid' : 'failed',
            'amount' => $amount,
            'card_last_digits' => substr($cardNumber, -4),
        ]);

        $this->transactionRepository->attachProducts($transaction, $products);
        $this->paymentAttemptRepository->createMany($transaction, $attempts);

        return $transaction;
    }
}
