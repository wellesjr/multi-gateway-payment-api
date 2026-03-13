<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

function makePaidTransactionForRefund(): Transaction
{
    $client = Client::factory()->create();
    $gateway = Gateway::factory()->create([
        'name' => 'gateway1',
        'is_active' => true,
        'priority' => 1,
    ]);

    return Transaction::query()->create([
        'client_id' => $client->id,
        'gateway_id' => $gateway->id,
        'external_id' => 'refund-idemp-' . fake()->numerify('###'),
        'status' => TransactionStatus::Paid->value,
        'amount' => 99.90,
        'card_last_digits' => '1111',
    ]);
}

test('reembolso com mesmo Idempotency-Key não executa gateway duas vezes', function () {
    /** @var TestCase $this */
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
    $transaction = makePaidTransactionForRefund();
    $headers = ['Idempotency-Key' => 'refund-key-001'];

    $first = $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund", [], $headers);

    $second = $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund", [], $headers);

    $first->assertStatus(200)->assertJsonPath('data.status', TransactionStatus::Refunded->value);
    $second->assertStatus(200)->assertJsonPath('data.status', TransactionStatus::Refunded->value);

    Http::assertSentCount(2);
});

test('reembolso com mesmo Idempotency-Key em transações diferentes retorna erro', function () {
    /** @var TestCase $this */
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
    $transactionA = makePaidTransactionForRefund();
    $transactionB = makePaidTransactionForRefund();
    $headers = ['Idempotency-Key' => 'refund-key-002'];

    $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transactionA->id}/refund", [], $headers)
        ->assertStatus(200);

    $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transactionB->id}/refund", [], $headers)
        ->assertStatus(422)
        ->assertJsonPath('message', 'Idempotency-Key já utilizado com outra transação de reembolso.');
});
