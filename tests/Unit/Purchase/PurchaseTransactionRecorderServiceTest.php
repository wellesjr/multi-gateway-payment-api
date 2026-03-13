<?php

namespace Tests\Unit\Purchase;

use App\Dtos\Payment\PaymentAttemptDto;
use App\Enums\PaymentAttemptStatus;
use App\Enums\TransactionStatus;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Services\Purchase\PurchaseTransactionRecorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTransactionRecorderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_paid_transaction_with_products(): void
    {
        $client = Client::factory()->create();
        $gateway = Gateway::factory()->create(['name' => 'gateway1']);
        $product = Product::factory()->create();

        $service = app(PurchaseTransactionRecorderService::class);

        $transaction = $service->record(
            client: $client,
            gateway: $gateway,
            externalId: 'ext-123',
            amount: 80.30,
            cardNumber: '5569000000006063',
            paid: true,
            products: [
                ['id' => $product->id, 'quantity' => 2],
            ],
            attempts: [
                new PaymentAttemptDto(
                    gatewayId: $gateway->id,
                    gatewayName: $gateway->name,
                    status: PaymentAttemptStatus::Success,
                    externalId: 'ext-123',
                    errorMessage: null,
                    attemptedAt: now(),
                ),
            ],
        );

        $this->assertSame(TransactionStatus::Paid, $transaction->status);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext-123',
            'status' => 'paid',
            'card_last_digits' => '6063',
        ]);

        $this->assertDatabaseHas('transaction_products', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'transaction_id' => $transaction->id,
            'gateway_id' => $gateway->id,
            'status' => 'success',
            'external_id' => 'ext-123',
        ]);
    }

    public function test_it_records_failed_transaction_without_gateway(): void
    {
        $client = Client::factory()->create();
        $product = Product::factory()->create();

        $service = app(PurchaseTransactionRecorderService::class);

        $transaction = $service->record(
            client: $client,
            gateway: null,
            externalId: null,
            amount: 10.00,
            cardNumber: '4111111111111111',
            paid: false,
            products: [
                ['id' => $product->id, 'quantity' => 1],
            ],
        );

        $this->assertSame(TransactionStatus::Failed, $transaction->status);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'client_id' => $client->id,
            'gateway_id' => null,
            'external_id' => null,
            'status' => 'failed',
            'card_last_digits' => '1111',
        ]);

        $this->assertDatabaseCount('payment_attempts', 0);
    }
}
