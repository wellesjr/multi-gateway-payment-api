<?php

namespace App\Services;

use App\Dtos\Purchase\PurchaseDto;
use App\UseCases\Purchase\FinalizePurchaseUseCase;
use App\UseCases\Purchase\ResolvePurchasePaymentUseCase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly ResolvePurchasePaymentUseCase $resolvePurchasePaymentUseCase,
        private readonly FinalizePurchaseUseCase $finalizePurchaseUseCase,
    ) {}

    public function purchase(PurchaseDto $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $client = $this->clientService->findOrCreate(
                $dto->clientName,
                $dto->clientEmail,
            );

            $resolvedPayment = $this->resolvePurchasePaymentUseCase->execute($dto, $client);

            return $this->finalizePurchaseUseCase->execute(
                client: $client,
                dto: $dto,
                calculatedPurchase: $resolvedPayment['calculatedPurchase'],
                payment: $resolvedPayment['payment'],
            );
        });
    }
}
