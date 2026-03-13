<?php

namespace App\Jobs;

use App\Services\FinancialReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileFinancialTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $transactionId,
    ) {
        $this->onQueue('financial-reconciliation');
    }

    public function handle(FinancialReconciliationService $financialReconciliationService): void
    {
        $financialReconciliationService->reconcile($this->transactionId);
    }
}
