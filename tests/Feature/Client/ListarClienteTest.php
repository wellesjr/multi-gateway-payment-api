<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('listar clientes requer autenticação', function () {

    /** @var TestCase $this */
    $this->getJson('/api/v1/clients')->assertStatus(401);
});

test('usuário com role USER não pode listar clientes', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->getJson('/api/v1/clients')
        ->assertStatus(403);
});

test('ADMIN pode listar clientes', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Client::factory()->count(3)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/clients');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [['id', 'name', 'email']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJson(['success' => true]);
});

test('MANAGER pode listar clientes', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    Client::factory()->count(2)->create();

    $response = $this->actingAs($manager)
        ->getJson('/api/v1/clients');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('FINANCE pode listar clientes', function () {

    /** @var TestCase $this
     * @var User $finance
     */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    Client::factory()->count(2)->create();

    $response = $this->actingAs($finance)
        ->getJson('/api/v1/clients');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('listar clientes retorna paginação correta', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Client::factory()->count(5)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/clients');

    $response->assertStatus(200)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.current_page', 1);
});
