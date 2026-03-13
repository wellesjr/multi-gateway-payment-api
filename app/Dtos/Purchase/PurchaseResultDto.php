<?php

namespace App\Dtos\Purchase;

use App\Models\Transaction;

readonly class PurchaseResultDto
{
    public function __construct(
        public bool $success,
        public Transaction $transaction,
        public string $message,
    ) {}
}
