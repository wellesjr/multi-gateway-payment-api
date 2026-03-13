<?php

namespace App\Services\Purchase;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class PurchaseTransactionRecorderService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * @param array<int, array{id: int, quantity: int}> $products
     */
    public function record(
        Client $client,
        ?Gateway $gateway,
        ?string $externalId,
        float $amount,
        string $cardNumber,
        bool $paid,
        array $products,
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

        return $transaction;
    }
}
