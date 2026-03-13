<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;

interface PaymentAttemptRepositoryInterface
{
    public function createMany(Transaction $transaction, array $attempts): void;
}
