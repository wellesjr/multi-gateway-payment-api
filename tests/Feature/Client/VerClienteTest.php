<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('ver cliente requer autenticação', function () {

    /** @var TestCase $this */
    $client = Client::factory()->create();

    $this->getJson("/api/v1/clients/{$client->id}")->assertStatus(401);
});

test('usuário com role USER não pode ver cliente', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user   = User::factory()->create(['role' => UserRole::User]);
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(403);
});

test('ADMIN pode ver cliente', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $client = Client::factory()->create();

    $response = $this->actingAs($admin)
        ->getJson("/api/v1/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id'    => $client->id,
                'name'  => $client->name,
                'email' => $client->email,
            ],
        ]);
});

test('MANAGER pode ver cliente', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $client  = Client::factory()->create();

    $this->actingAs($manager)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(200);
});

test('FINANCE pode ver cliente', function () {

    /** @var TestCase $this
     * @var User $finance
     */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $client  = Client::factory()->create();

    $this->actingAs($finance)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(200);
});

test('ver cliente retorna estrutura correta', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $client = Client::factory()->create([
        'name'  => 'João Silva',
        'email' => 'joao@example.com',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/api/v1/clients/{$client->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'email']])
        ->assertJson([
            'data' => [
                'name'  => 'João Silva',
                'email' => 'joao@example.com',
            ],
        ]);
});
