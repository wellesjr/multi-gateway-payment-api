<?php

namespace App\Services;

use App\Dtos\Purchase\PurchaseDto;
use App\Models\Product;
use App\Models\Transaction;
use App\Dtos\Payment\ChargePayloadDto;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly PaymentOrchestratorService $paymentOrchestrator,
    ) {}

    /**
     * @return array{success: bool, transaction: Transaction, message: string}
     */
    public function purchase(PurchaseDto $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $client = $this->clientService->findOrCreate(
                $dto->clientName,
                $dto->clientEmail,
            );

            $products = Product::whereIn(
                'id',
                collect($dto->products)->pluck('id'),
            )->get();

            $amount = 0;

            foreach ($dto->products as $item) {
                $product = $products->firstWhere('id', $item['id']);

                if (!$product) {
                    throw new \DomainException('Produto informado não foi encontrado.');
                }

                $amount += (float) $product->amount * $item['quantity'];
            }

            $payment = $this->paymentOrchestrator->charge(new ChargePayloadDto(
                amountInCents: (int) round($amount * 100),
                name: $client->name,
                email: $client->email,
                cardNumber: $dto->cardNumber,
                cvv: $dto->cvv,
            ));

            $transaction = Transaction::create([
                'client_id' => $client->id,
                'gateway_id' => $payment['gateway']->id ?? null,
                'external_id' => $payment['external_id'] ?? null,
                'status' => $payment['success'] ? 'paid' : 'failed',
                'amount' => $amount,
                'card_last_digits' => substr($dto->cardNumber, -4),
            ]);

            foreach ($dto->products as $item) {
                $transaction->products()->attach(
                    $item['id'],
                    ['quantity' => $item['quantity']],
                );
            }

            if (!$payment['success']) {
                return [
                    'success' => false,
                    'transaction' => $transaction,
                    'message' => 'Não foi possível processar a compra em nenhum gateway.',
                ];
            }

            return [
                'success' => true,
                'transaction' => $transaction->fresh(['client', 'gateway', 'products']),
                'message' => 'Compra realizada com sucesso.',
            ];
        });
    }
}
