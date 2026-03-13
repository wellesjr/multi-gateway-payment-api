<?php

namespace App\Dtos\Purchase;

use App\Dtos\Payment\PaymentChargeResultDto;

readonly class ResolvedPurchasePaymentDto
{
    public function __construct(
        public CalculatedPurchaseDto $calculatedPurchase,
        public PaymentChargeResultDto $payment,
    ) {}
}
