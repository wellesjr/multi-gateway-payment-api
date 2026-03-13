<?php

namespace App\Repositories;

use App\Dtos\Payment\PaymentAttemptDto;
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
                'gateway_id' => $attempt->gatewayId,
                'status' => $attempt->status->value,
                'external_id' => $attempt->externalId,
                'error_message' => $attempt->errorMessage,
                'attempted_at' => $attempt->attemptedAt,
            ]);
        }
    }
}
