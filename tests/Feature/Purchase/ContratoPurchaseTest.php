<?php

use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('contrato da rota pública POST /purchase', function () {
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
    $product = Product::factory()->create(['amount' => 35.90]);

    Http::fake([
        'http://gateway1.local/login' => Http::response(['token' => 'GW1_BEARER_TOKEN'], 200),
        'http://gateway1.local/transactions' => Http::response(['id' => 'gw1-contract-001'], 201),
        'http://gateway2.local/*' => Http::response([], 500),
    ]);

    $payload = [
        'client' => [
            'name' => 'Cliente Contrato',
            'email' => 'contrato@example.com',
        ],
        'products' => [
            ['id' => $product->id, 'quantity' => 2],
        ],
        'card_number' => '5569000000006063',
        'cvv' => '010',
    ];

    $this->postJson('/api/purchase', $payload)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'status',
                'amount',
                'external_id',
                'card_last_digits',
                'gateway' => ['id', 'name', 'priority'],
                'products',
                'payment_attempts',
            ],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.external_id', 'gw1-contract-001');
});
