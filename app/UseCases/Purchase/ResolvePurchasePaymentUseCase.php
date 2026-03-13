<?php

namespace App\UseCases\Purchase;

use App\Dtos\Payment\ChargePayloadDto;
use App\Dtos\Purchase\CalculatedPurchaseDto;
use App\Dtos\Purchase\PurchaseDto;
use App\Models\Client;
use App\Services\Payment\PaymentOrchestratorService;
use App\Services\Purchase\PurchaseAmountCalculatorService;

class ResolvePurchasePaymentUseCase
{
    public function __construct(
        private readonly PurchaseAmountCalculatorService $purchaseAmountCalculator,
        private readonly PaymentOrchestratorService $paymentOrchestrator,
    ) {}

    public function execute(PurchaseDto $dto, Client $client): array
    {
        $calculatedPurchase = $this->purchaseAmountCalculator->calculate($dto->products);

        $payment = $this->paymentOrchestrator->charge(new ChargePayloadDto(
            amountInCents: (int) round($calculatedPurchase->amount * 100),
            name: $client->name,
            email: $client->email,
            cardNumber: $dto->cardNumber,
            cvv: $dto->cvv,
        ));

        return [
            'calculatedPurchase' => $calculatedPurchase,
            'payment' => $payment,
        ];
    }
}
