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

function createTransactionForTests(array $overrides = []): Transaction
{
    $client = Client::factory()->create();
    $gateway = Gateway::factory()->create([
        'name' => 'gateway1',
        'is_active' => true,
        'priority' => 1,
    ]);

    return Transaction::query()->create(array_merge([
        'client_id' => $client->id,
        'gateway_id' => $gateway->id,
        'external_id' => 'txn-ext-001',
        'status' => TransactionStatus::Paid->value,
        'amount' => 100.00,
        'card_last_digits' => '1111',
    ], $overrides));
}

test('rotas de transação list/show requerem autenticação', function () {
    /** @var TestCase $this */
    $transaction = createTransactionForTests();

    $this->getJson('/api/v1/transactions')->assertStatus(401);
    $this->getJson("/api/v1/transactions/{$transaction->id}")->assertStatus(401);
});

test('ADMIN pode listar e visualizar transações', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $transaction = createTransactionForTests();

    $this->actingAs($admin)
        ->getJson('/api/v1/transactions')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->actingAs($admin)
        ->getJson("/api/v1/transactions/{$transaction->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $transaction->id);
});

test('somente ADMIN pode listar e visualizar transações', function (UserRole $role) {
    /** @var TestCase $this */
    $user = User::factory()->create(['role' => $role]);
    $transaction = createTransactionForTests();

    $this->actingAs($user)
        ->getJson('/api/v1/transactions')
        ->assertStatus(403);

    $this->actingAs($user)
        ->getJson("/api/v1/transactions/{$transaction->id}")
        ->assertStatus(403);
})->with([
    UserRole::Manager,
    UserRole::Finance,
    UserRole::User,
]);

test('ADMIN e FINANCE podem reembolsar transação paga', function (UserRole $role) {
    /** @var TestCase $this */
    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
    ]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'token' => 'GW1_TOKEN',
        ], 200),
        'http://gateway1.local/transactions/*/charge_back' => Http::response([], 200),
    ]);

    $transaction = createTransactionForTests();
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::Refunded->value);

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => TransactionStatus::Refunded->value,
    ]);
})->with([
    UserRole::Admin,
    UserRole::Finance,
]);

test('MANAGER e USER não podem reembolsar transação', function (UserRole $role) {
    /** @var TestCase $this */
    $transaction = createTransactionForTests();
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund")
        ->assertStatus(403);
})->with([
    UserRole::Manager,
    UserRole::User,
]);

test('reembolso falha para transação não paga', function () {
    /** @var TestCase $this */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $transaction = createTransactionForTests([
        'status' => TransactionStatus::Failed->value,
    ]);

    $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Somente transações pagas podem ser reembolsadas.');
});

test('reembolso retorna erro quando gateway nega operação', function () {
    /** @var TestCase $this */
    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
    ]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'token' => 'GW1_TOKEN',
        ], 200),
        'http://gateway1.local/transactions/*/charge_back' => Http::response([], 422),
    ]);

    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $transaction = createTransactionForTests();

    $this->actingAs($finance)
        ->postJson("/api/v1/transactions/{$transaction->id}/refund")
        ->assertStatus(422)
        ->assertJsonPath('message', 'O gateway não autorizou o reembolso.');
});
