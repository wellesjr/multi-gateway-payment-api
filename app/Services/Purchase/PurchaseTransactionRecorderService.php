<?php

namespace App\Services\Purchase;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;

class PurchaseTransactionRecorderService
{
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
        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway?->id,
            'external_id' => $externalId,
            'status' => $paid ? 'paid' : 'failed',
            'amount' => $amount,
            'card_last_digits' => substr($cardNumber, -4),
        ]);

        foreach ($products as $item) {
            $transaction->products()->attach(
                $item['id'],
                ['quantity' => $item['quantity']],
            );
        }

        return $transaction;
    }
}
