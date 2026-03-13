<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('listar produtos requer autenticação', function () {

    /** @var TestCase $this */
    $this->getJson('/api/v1/products')->assertStatus(401);
});

test('usuário com role USER não pode listar produtos', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->getJson('/api/v1/products')
        ->assertStatus(403);
});

test('usuário com role FINANCE pode listar produtos', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::Finance]);

    $this->actingAs($user)
        ->getJson('/api/v1/products')
        ->assertStatus(200);
});

test('ADMIN pode listar produtos', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Product::factory()->count(5)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [['id', 'name', 'amount', 'created_at', 'updated_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJson(['success' => true]);
});

test('MANAGER pode listar produtos', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->getJson('/api/v1/products')
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('listagem de produtos é paginada', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Product::factory()->count(20)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/products');

    $response->assertStatus(200);
    $meta = $response->json('meta');
    expect($meta['per_page'])->toBe(15);
    expect($meta['total'])->toBe(20);
});

test('visualizar produto requer autenticação', function () {

    /** @var TestCase $this */
    $product = Product::factory()->create();

    $this->getJson("/api/v1/products/{$product->id}")->assertStatus(401);
});

test('usuário com role USER não pode visualizar produto', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/products/{$product->id}")
        ->assertStatus(403);
});

test('ADMIN pode visualizar um produto', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create(['name' => 'Produto X', 'amount' => 99.90]);

    $response = $this->actingAs($admin)
        ->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Produto X',
                'amount' => '99.90',
            ],
        ]);
});

test('MANAGER pode visualizar um produto', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $product = Product::factory()->create();

    $this->actingAs($manager)
        ->getJson("/api/v1/products/{$product->id}")
        ->assertStatus(200);
});

test('FINANCE pode visualizar um produto', function () {

    /** @var TestCase $this
     * @var User $finance
     */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $product = Product::factory()->create();

    $this->actingAs($finance)
        ->getJson("/api/v1/products/{$product->id}")
        ->assertStatus(200);
});

test('retorna 404 para produto inexistente', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->getJson('/api/v1/products/99999')
        ->assertStatus(404);
});
