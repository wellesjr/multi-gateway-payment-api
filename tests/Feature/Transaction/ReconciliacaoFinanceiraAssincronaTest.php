<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Jobs\ReconcileFinancialTransactionJob;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('compra dispara job assíncrono de reconciliação financeira', function () {
    /** @var TestCase $this */
    Queue::fake();

    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
        'services.gateway2.url' => 'http://gateway2.local',
        'services.gateway2.auth_token' => 'AUTH',
        'services.gateway2.auth_secret' => 'SECRET',
    ]);

    Gateway::factory()->create(['name' => 'gateway1', 'priority' => 1, 'is_active' => true]);
    Gateway::factory()->create(['name' => 'gateway2', 'priority' => 2, 'is_active' => true]);
    $product = Product::factory()->create(['amount' => 10.00]);

    Http::fake([
        'http://gateway1.local/login' => Http::response(['token' => 'GW1_TOKEN'], 200),
        'http://gateway1.local/transactions' => Http::response(['id' => 'gw1-async-001'], 201),
        'http://gateway2.local/*' => Http::response([], 500),
    ]);

    $this->postJson('/api/purchase', [
        'client' => [
            'name' => 'Cliente Async',
            'email' => 'async@example.com',
        ],
        'products' => [
            ['id' => $product->id, 'quantity' => 1],
        ],
        'card_number' => '5569000000006063',
        'cvv' => '010',
    ])->assertStatus(201);

    Queue::assertPushed(ReconcileFinancialTransactionJob::class, 1);
});

test('reembolso dispara job assíncrono de reconciliação financeira', function () {
    /** @var TestCase $this */
    Queue::fake();

    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
    ]);

    Http::fake([
        'http://gateway1.local/login' => Http::response(['token' => 'GW1_TOKEN'], 200),
        'http://gateway1.local/transactions/*/charge_back' => Http::response([], 200),
    ]);

    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $client = Client::factory()->create();
    $gateway = Gateway::factory()->create(['name' => 'gateway1', 'is_active' => true, 'priority' => 1]);
    $transaction = Transaction::query()->create([
        'client_id' => $client->id,
        'gateway_id' => $gateway->id,
        'external_id' => 'gw1-async-refund-001',
        'status' => TransactionStatus::Paid->value,
        'amount' => 50.00,
        'card_last_digits' => '1111',
    ]);

    $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund")
        ->assertStatus(200);

    Queue::assertPushed(ReconcileFinancialTransactionJob::class, 1);
});
