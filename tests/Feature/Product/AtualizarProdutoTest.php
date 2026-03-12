<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('atualizar produto requer autenticação', function () {

    /** @var TestCase $this */
    $product = Product::factory()->create();

    $this->putJson("/api/v1/products/{$product->id}", ['name' => 'Novo Nome'])
        ->assertStatus(401);
});

test('usuário com role USER não pode atualizar produto', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user    = User::factory()->create(['role' => UserRole::User]);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/v1/products/{$product->id}", ['name' => 'Hackeado'])
        ->assertStatus(403);
});

test('usuário com role FINANCE não pode atualizar produto', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user    = User::factory()->create(['role' => UserRole::Finance]);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/v1/products/{$product->id}", ['name' => 'Tentativa'])
        ->assertStatus(403);
});

test('ADMIN pode atualizar produto', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create(['name' => 'Original', 'amount' => 10.00]);

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['name' => 'Atualizado']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Produto atualizado com sucesso.',
            'data'    => ['name' => 'Atualizado'],
        ]);

    $this->assertDatabaseHas('products', [
        'id'   => $product->id,
        'name' => 'Atualizado',
    ]);
});

test('MANAGER pode atualizar produto', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $product = Product::factory()->create();

    $this->actingAs($manager)
        ->putJson("/api/v1/products/{$product->id}", ['name' => 'Atualizado pelo Manager'])
        ->assertStatus(200);
});

test('ADMIN pode atualizar apenas o nome do produto', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create(['name' => 'Original', 'amount' => 50.00]);

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['name' => 'Novo Nome'])
        ->assertStatus(200);

    $this->assertDatabaseHas('products', [
        'id'     => $product->id,
        'name'   => 'Novo Nome',
        'amount' => 50.00,
    ]);
});

test('ADMIN pode atualizar apenas o valor do produto', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create(['name' => 'Produto', 'amount' => 10.00]);

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['amount' => 99.99])
        ->assertStatus(200);

    $this->assertDatabaseHas('products', [
        'id'     => $product->id,
        'name'   => 'Produto',
        'amount' => 99.99,
    ]);
});

test('atualizar produto sem dados retorna nenhuma alteração', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", [])
        ->assertStatus(204);
});

test('atualizar produto falha com nome maior que 255 caracteres', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", [
            'name' => str_repeat('a', 256),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('atualizar produto falha com valor negativo', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['amount' => -1.00])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('atualizar produto falha com mais de 2 casas decimais', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['amount' => 10.123])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('atualizar produto aceita valor inteiro', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin   = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/products/{$product->id}", ['amount' => 10])
        ->assertStatus(200);
});

test('atualizar produto inexistente retorna 404', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->putJson('/api/v1/products/99999', ['name' => 'Nada'])
        ->assertStatus(404);
});
