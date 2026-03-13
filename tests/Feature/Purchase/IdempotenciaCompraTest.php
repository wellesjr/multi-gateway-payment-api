<?php

use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

function purchasePayloadForIdempotency(int $productId, int $quantity = 1): array
{
    return [
        'client' => [
            'name' => 'Cliente Idempotente',
            'email' => 'idempotencia@example.com',
        ],
        'products' => [
            ['id' => $productId, 'quantity' => $quantity],
        ],
        'card_number' => '5569000000006063',
        'cvv' => '010',
    ];
}

test('compra com mesmo Idempotency-Key não cria transações duplicadas', function () {
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
    $product = Product::factory()->create(['amount' => 20.00]);

    Http::fake([
        'http://gateway1.local/login' => Http::response(['token' => 'GW1_BEARER_TOKEN'], 200),
        'http://gateway1.local/transactions' => Http::response(['id' => 'gw1-idempotency-001'], 201),
        'http://gateway2.local/*' => Http::response([], 500),
    ]);

    $headers = ['Idempotency-Key' => 'purchase-key-001'];

    $first = $this->postJson('/api/purchase', purchasePayloadForIdempotency($product->id), $headers);
    $second = $this->postJson('/api/purchase', purchasePayloadForIdempotency($product->id), $headers);

    $first->assertStatus(201)->assertJsonPath('success', true);
    $second->assertStatus(200)->assertJsonPath('message', 'Compra já processada anteriormente.');

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    $this->assertDatabaseCount('transactions', 1);

    Http::assertSentCount(2);
});

test('compra com mesmo Idempotency-Key e payload diferente retorna erro', function () {
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
    $product = Product::factory()->create(['amount' => 20.00]);

    Http::fake([
        'http://gateway1.local/login' => Http::response(['token' => 'GW1_BEARER_TOKEN'], 200),
        'http://gateway1.local/transactions' => Http::response(['id' => 'gw1-idempotency-002'], 201),
        'http://gateway2.local/*' => Http::response([], 500),
    ]);

    $headers = ['Idempotency-Key' => 'purchase-key-002'];

    $this->postJson('/api/purchase', purchasePayloadForIdempotency($product->id, 1), $headers)
        ->assertStatus(201);

    $this->postJson('/api/purchase', purchasePayloadForIdempotency($product->id, 2), $headers)
        ->assertStatus(422)
        ->assertJsonPath('message', 'Idempotency-Key já utilizado com payload diferente.');
});
