<?php

use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

function payloadCompraTimeout(int $productId): array
{
    return [
        'client' => [
            'name' => 'Cliente Timeout',
            'email' => 'timeout@example.com',
        ],
        'products' => [
            ['id' => $productId, 'quantity' => 1],
        ],
        'card_number' => '5569000000006063',
        'cvv' => '010',
    ];
}

test('fallback tenta próximo gateway quando ocorre timeout/conexão', function () {
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
    $product = Product::factory()->create(['amount' => 10.00]);

    Http::fake([
        'http://gateway1.local/login' => function () {
            throw new ConnectionException('timeout ao conectar no gateway1');
        },
        'http://gateway2.local/transacoes' => Http::response([
            'id' => 'gw2-timeout-fallback-001',
            'status' => 'ok',
        ], 201),
    ]);

    $this->postJson('/api/purchase', payloadCompraTimeout($product->id))
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.gateway.id', $gateway2->id)
        ->assertJsonPath('data.external_id', 'gw2-timeout-fallback-001');

    $this->assertDatabaseHas('payment_attempts', [
        'status' => 'exception',
        'error_message' => 'timeout ao conectar no gateway1',
    ]);
    $this->assertDatabaseHas('payment_attempts', [
        'gateway_id' => $gateway2->id,
        'status' => 'success',
        'external_id' => 'gw2-timeout-fallback-001',
    ]);
});

test('retorna erro quando timeout ocorre e nenhum gateway processa a compra', function () {
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
    $product = Product::factory()->create(['amount' => 10.00]);

    Http::fake([
        'http://gateway1.local/login' => function () {
            throw new ConnectionException('timeout geral');
        },
        'http://gateway2.local/transacoes' => Http::response([
            'message' => 'erro gateway2',
        ], 422),
    ]);

    $this->postJson('/api/purchase', payloadCompraTimeout($product->id))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Não foi possível processar a compra em nenhum gateway.');
});
