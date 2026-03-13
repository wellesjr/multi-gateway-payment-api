<?php

use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

function payloadCompra(array $products): array
{
    return [
        'client' => [
            'name' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
        ],
        'products' => $products,
        'card_number' => '5569000000006063',
        'cvv' => '010',
    ];
}

test('realiza compra com sucesso no primeiro gateway ativo por prioridade', function () {
    /** @var TestCase $this */

    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
        'services.gateway2.url' => 'http://gateway2.local',
        'services.gateway2.auth_token' => 'AUTH',
        'services.gateway2.auth_secret' => 'SECRET',
    ]);

    $gateway1 = Gateway::factory()->create(['name' => 'gateway1', 'priority' => 1, 'is_active' => true]);
    Gateway::factory()->create(['name' => 'gateway2', 'priority' => 2, 'is_active' => true]);

    $productA = Product::factory()->create(['amount' => 10.00]);
    $productB = Product::factory()->create(['amount' => 5.00]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'email' => 'dev@betalent.tech',
            'token' => 'GW1_BEARER_TOKEN',
        ], 200),
        'http://gateway1.local/transactions' => Http::response([
            'id' => 'gw1-ext-123',
            'status' => 'success',
        ], 201),
        'http://gateway2.local/*' => Http::response([], 500),
    ]);

    $response = $this->postJson('/api/purchase', payloadCompra([
        ['id' => $productA->id, 'quantity' => 2],
        ['id' => $productB->id, 'quantity' => 1],
    ]));

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.gateway.id', $gateway1->id)
        ->assertJsonPath('data.external_id', 'gw1-ext-123');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'http://gateway1.local/transactions') {
            return false;
        }

        return ((int) ($request['amount'] ?? 0)) === 2500;
    });

    $this->assertDatabaseHas('transactions', [
        'gateway_id' => $gateway1->id,
        'status' => 'paid',
        'external_id' => 'gw1-ext-123',
        'amount' => 25.00,
    ]);

    $this->assertDatabaseHas('transaction_products', [
        'product_id' => $productA->id,
        'quantity' => 2,
    ]);

    $this->assertDatabaseHas('payment_attempts', [
        'gateway_id' => $gateway1->id,
        'status' => 'success',
        'external_id' => 'gw1-ext-123',
    ]);
});

test('quando primeiro gateway falha tenta o segundo gateway e retorna sucesso', function () {
    /** @var TestCase $this */

    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
        'services.gateway2.url' => 'http://gateway2.local',
        'services.gateway2.auth_token' => 'AUTH',
        'services.gateway2.auth_secret' => 'SECRET',
    ]);

    Gateway::factory()->create(['name' => 'gateway1', 'priority' => 1, 'is_active' => true]);
    $gateway2 = Gateway::factory()->create(['name' => 'gateway2', 'priority' => 2, 'is_active' => true]);

    $product = Product::factory()->create(['amount' => 49.90]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'email' => 'dev@betalent.tech',
            'token' => 'GW1_BEARER_TOKEN',
        ], 200),
        'http://gateway1.local/transactions' => Http::response([
            'message' => 'dados inválidos',
        ], 422),
        'http://gateway2.local/transacoes' => Http::response([
            'id' => 'gw2-ext-xyz',
            'status' => 'ok',
        ], 201),
    ]);

    $response = $this->postJson('/api/purchase', payloadCompra([
        ['id' => $product->id, 'quantity' => 1],
    ]));

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.gateway.id', $gateway2->id)
        ->assertJsonPath('data.external_id', 'gw2-ext-xyz');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'http://gateway2.local/transacoes') {
            return false;
        }

        return ((int) ($request['valor'] ?? 0)) === 4990;
    });

    $this->assertDatabaseHas('payment_attempts', [
        'gateway_id' => $gateway2->id,
        'status' => 'success',
        'external_id' => 'gw2-ext-xyz',
    ]);
    $this->assertDatabaseHas('payment_attempts', [
        'status' => 'failed',
        'error_message' => 'Gateway 1 retornou erro ao cobrar.',
    ]);
});

test('quando gateway prioritário lança exceção esperada tenta o próximo gateway', function () {
    /** @var TestCase $this */

    config([
        'services.gateway1.url' => 'http://gateway1.local',
        'services.gateway1.email' => 'dev@betalent.tech',
        'services.gateway1.token' => 'TOKEN',
        'services.gateway2.url' => 'http://gateway2.local',
        'services.gateway2.auth_token' => 'AUTH',
        'services.gateway2.auth_secret' => 'SECRET',
    ]);

    Gateway::factory()->create(['name' => 'gateway1', 'priority' => 1, 'is_active' => true]);
    $gateway2 = Gateway::factory()->create(['name' => 'gateway2', 'priority' => 2, 'is_active' => true]);

    $product = Product::factory()->create(['amount' => 19.90]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'message' => 'falha de autenticação',
        ], 500),
        'http://gateway2.local/transacoes' => Http::response([
            'id' => 'gw2-ext-after-exception',
            'status' => 'ok',
        ], 201),
    ]);

    $response = $this->postJson('/api/purchase', payloadCompra([
        ['id' => $product->id, 'quantity' => 1],
    ]));

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.gateway.id', $gateway2->id)
        ->assertJsonPath('data.external_id', 'gw2-ext-after-exception');

    $this->assertDatabaseHas('payment_attempts', [
        'status' => 'exception',
        'error_message' => 'Falha ao autenticar no Gateway 1.',
    ]);
    $this->assertDatabaseHas('payment_attempts', [
        'gateway_id' => $gateway2->id,
        'status' => 'success',
        'external_id' => 'gw2-ext-after-exception',
    ]);
});

test('quando todos os gateways falham retorna erro e registra transação como failed', function () {
    /** @var TestCase $this */

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

    $product = Product::factory()->create(['amount' => 12.50]);

    Http::fake([
        'http://gateway1.local/login' => Http::response([
            'email' => 'dev@betalent.tech',
            'token' => 'GW1_BEARER_TOKEN',
        ], 200),
        'http://gateway1.local/transactions' => Http::response(['message' => 'erro 1'], 422),
        'http://gateway2.local/transacoes' => Http::response(['message' => 'erro 2'], 422),
    ]);

    $response = $this->postJson('/api/purchase', payloadCompra([
        ['id' => $product->id, 'quantity' => 2],
    ]));

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Não foi possível processar a compra em nenhum gateway.');

    $this->assertDatabaseHas('transactions', [
        'status' => 'failed',
        'amount' => 25.00,
    ]);

    $this->assertDatabaseHas('payment_attempts', [
        'status' => 'failed',
        'error_message' => 'Gateway 1 retornou erro ao cobrar.',
    ]);
    $this->assertDatabaseHas('payment_attempts', [
        'status' => 'failed',
        'error_message' => 'Gateway 2 retornou erro ao cobrar.',
    ]);
});

test('valida payload de compra', function () {
    /** @var TestCase $this */

    $response = $this->postJson('/api/purchase', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['client.name', 'client.email', 'products', 'card_number', 'cvv']);
});
