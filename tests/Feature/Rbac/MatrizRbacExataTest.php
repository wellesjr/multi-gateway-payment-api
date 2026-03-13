<?php

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('matriz RBAC segue exatamente o desafio', function () {
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

    $gateway = Gateway::factory()->create(['name' => 'gateway1', 'is_active' => true, 'priority' => 1]);
    $client = Client::factory()->create();
    $product = Product::factory()->create();

    $roles = [
        'ADMIN' => UserRole::Admin,
        'MANAGER' => UserRole::Manager,
        'FINANCE' => UserRole::Finance,
        'USER' => UserRole::User,
    ];

    $operations = [
        'users.index' => [
            'request' => fn () => ['GET', '/api/v1/users', []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 403, 'USER' => 403],
        ],
        'users.show' => [
            'request' => fn () => ['GET', '/api/v1/users/' . User::factory()->create(['role' => UserRole::User])->id, []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 403, 'USER' => 403],
        ],
        'users.store' => [
            'request' => fn () => ['POST', '/api/v1/users', [
                'name' => 'Novo',
                'email' => fake()->unique()->userName() . '@email.com',
                'password' => 'senha12345',
                'password_confirmation' => 'senha12345',
                'role' => 'USER',
            ]],
            'expected' => ['ADMIN' => 201, 'MANAGER' => 201, 'FINANCE' => 403, 'USER' => 403],
        ],
        'users.update' => [
            'request' => fn () => ['PUT', '/api/v1/users/' . User::factory()->create(['role' => UserRole::User])->id, [
                'name' => 'Atualizado',
            ]],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 403, 'USER' => 403],
        ],
        'users.destroy' => [
            'request' => fn () => ['DELETE', '/api/v1/users/' . User::factory()->create(['role' => UserRole::User])->id, []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 403, 'USER' => 403],
        ],
        'products.index' => [
            'request' => fn () => ['GET', '/api/v1/products', []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 200, 'USER' => 403],
        ],
        'products.show' => [
            'request' => fn () => ['GET', "/api/v1/products/{$product->id}", []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 200, 'USER' => 403],
        ],
        'products.store' => [
            'request' => fn () => ['POST', '/api/v1/products', [
                'name' => 'Produto RBAC',
                'amount' => 10.00,
            ]],
            'expected' => ['ADMIN' => 201, 'MANAGER' => 201, 'FINANCE' => 201, 'USER' => 403],
        ],
        'products.update' => [
            'request' => fn () => ['PUT', "/api/v1/products/{$product->id}", [
                'name' => 'Produto Atualizado',
            ]],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 200, 'USER' => 403],
        ],
        'products.destroy' => [
            'request' => fn () => ['DELETE', '/api/v1/products/' . Product::factory()->create()->id, []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 200, 'FINANCE' => 200, 'USER' => 403],
        ],
        'clients.index' => [
            'request' => fn () => ['GET', '/api/v1/clients', []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
        'clients.show' => [
            'request' => fn () => ['GET', "/api/v1/clients/{$client->id}", []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
        'transactions.index' => [
            'request' => fn () => ['GET', '/api/v1/transactions', []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
        'transactions.show' => [
            'request' => fn () => ['GET', '/api/v1/transactions/' . Transaction::query()->create([
                'client_id' => $client->id,
                'gateway_id' => $gateway->id,
                'external_id' => 'txn-rbac-show',
                'status' => TransactionStatus::Paid->value,
                'amount' => 15.00,
                'card_last_digits' => '1111',
            ])->id, []],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
        'transactions.refund' => [
            'request' => function () use ($client, $gateway) {
                $transaction = Transaction::query()->create([
                    'client_id' => $client->id,
                    'gateway_id' => $gateway->id,
                    'external_id' => 'txn-rbac-refund-' . fake()->numerify('###'),
                    'status' => TransactionStatus::Paid->value,
                    'amount' => 15.00,
                    'card_last_digits' => '1111',
                ]);

                return ['POST', "/api/v1/transactions/{$transaction->id}/refund", []];
            },
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 200, 'USER' => 403],
        ],
        'gateways.status' => [
            'request' => fn () => ['PATCH', "/api/v1/gateways/{$gateway->id}/status", ['is_active' => false]],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
        'gateways.priority' => [
            'request' => fn () => ['PATCH', "/api/v1/gateways/{$gateway->id}/priority", ['priority' => 9]],
            'expected' => ['ADMIN' => 200, 'MANAGER' => 403, 'FINANCE' => 403, 'USER' => 403],
        ],
    ];

    foreach ($roles as $roleName => $role) {
        $actingUser = User::factory()->create(['role' => $role]);

        foreach ($operations as $operationName => $operation) {
            [$method, $url, $payload] = $operation['request']();

            $response = match ($method) {
                'GET' => $this->actingAs($actingUser)->getJson($url),
                'POST' => $this->actingAs($actingUser)->postJson($url, $payload),
                'PUT' => $this->actingAs($actingUser)->putJson($url, $payload),
                'PATCH' => $this->actingAs($actingUser)->patchJson($url, $payload),
                'DELETE' => $this->actingAs($actingUser)->deleteJson($url, $payload),
            };

            $expectedStatus = $operation['expected'][$roleName];
            $actualStatus = $response->getStatusCode();

            $this->assertSame(
                $expectedStatus,
                $actualStatus,
                "Falha em {$operationName} para role {$roleName}: esperado {$expectedStatus}, recebido {$actualStatus}.",
            );
        }
    }
});
