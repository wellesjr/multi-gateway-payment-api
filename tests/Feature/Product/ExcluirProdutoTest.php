<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('excluir produto requer autenticação', function () {

    /** @var TestCase $this */
    $product = Product::factory()->create();

    $this->deleteJson("/api/v1/products/{$product->id}")->assertStatus(401);
});

test('usuário com role USER não pode excluir produtos', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/products/{$product->id}")
        ->assertStatus(403);
});

test('usuário com role MANAGER pode excluir produtos', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $product = Product::factory()->create();

    $this->actingAs($manager)
        ->deleteJson("/api/v1/products/{$product->id}")
        ->assertStatus(200);
});

test('usuário com role FINANCE pode excluir produtos', function () {

    /** @var TestCase $this
     * @var User $finance
     */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $product = Product::factory()->create();

    $this->actingAs($finance)
        ->deleteJson("/api/v1/products/{$product->id}")
        ->assertStatus(200);
});

test('ADMIN pode excluir produto', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Produto excluído com sucesso.',
        ]);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('excluir produto inexistente retorna 404', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/products/99999')
        ->assertStatus(404);
});
