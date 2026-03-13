<?php

namespace App\Repositories;

use App\Models\PaymentAttempt;
use App\Models\Transaction;
use App\Repositories\Interfaces\PaymentAttemptRepositoryInterface;

class PaymentAttemptRepository implements PaymentAttemptRepositoryInterface
{
    public function __construct(
        private readonly PaymentAttempt $model,
    ) {}

    public function createMany(Transaction $transaction, array $attempts): void
    {
        foreach ($attempts as $attempt) {
            $this->model->newQuery()->create([
                'transaction_id' => $transaction->id,
                'gateway_id' => $attempt['gateway_id'],
                'status' => $attempt['status'],
                'external_id' => $attempt['external_id'],
                'error_message' => $attempt['error_message'],
                'attempted_at' => $attempt['attempted_at'] ?? now(),
            ]);
        }
    }
}
