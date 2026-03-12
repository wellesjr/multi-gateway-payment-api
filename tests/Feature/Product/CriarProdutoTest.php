<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('criar produto requer autenticação', function () {

    /** @var TestCase $this */
    $this->postJson('/api/v1/products', [])->assertStatus(401);
});

test('usuário com role USER não pode criar produtos', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->postJson('/api/v1/products', [
            'name' => 'Produto Teste',
            'amount' => 10.50,
        ])
        ->assertStatus(403);
});

test('usuário com role FINANCE não pode criar produtos', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::Finance]);

    $this->actingAs($user)
        ->postJson('/api/v1/products', [
            'name' => 'Produto Teste',
            'amount' => 10.50,
        ])
        ->assertStatus(403);
});

test('ADMIN pode criar produto com sucesso', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => 'Produto Novo',
            'amount' => 25.99,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Produto criado com sucesso.',
            'data' => [
                'name' => 'Produto Novo',
                'amount' => '25.99',
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Produto Novo',
        'amount' => 25.99,
    ]);
});

test('MANAGER pode criar produto com sucesso', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $response = $this->actingAs($manager)
        ->postJson('/api/v1/products', [
            'name' => 'Produto do Manager',
            'amount' => 50.00,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['name' => 'Produto do Manager']);
});

test('criar produto falha sem campos obrigatórios', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'amount']);
});

test('criar produto falha sem nome', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', ['amount' => 10.00])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('criar produto falha sem valor', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', ['name' => 'Produto'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('criar produto falha com nome maior que 255 caracteres', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => str_repeat('a', 256),
            'amount' => 10.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('criar produto falha com valor negativo', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => 'Produto',
            'amount' => -5.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('criar produto falha com mais de 2 casas decimais', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => 'Produto',
            'amount' => 10.123,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('criar produto aceita valor inteiro sem casas decimais', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => 'Produto Inteiro',
            'amount' => 10,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['name' => 'Produto Inteiro']);
});

test('criar produto aceita valor com 1 casa decimal', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/products', [
            'name' => 'Produto Decimal',
            'amount' => 10.5,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['name' => 'Produto Decimal']);
});
