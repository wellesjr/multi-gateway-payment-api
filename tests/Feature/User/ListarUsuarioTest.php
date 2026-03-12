<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('listar usuários requer autenticação', function () {

    /** @var \Tests\TestCase $this */

    $this->getJson('/api/users')->assertStatus(401);
});

test('usuário com role USER não pode listar usuários', function () {

/** @var \Tests\TestCase $this 
     * @var User $user
    */
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->getJson('/api/users')
        ->assertStatus(403);
});

test('usuário com role FINANCE não pode listar usuários', function () {
    
/** @var \Tests\TestCase $this 
     * @var User $user
    */

    $user = User::factory()->create(['role' => UserRole::Finance]);

    $this->actingAs($user)
        ->getJson('/api/users')
        ->assertStatus(403);
});

test('ADMIN pode listar usuários', function () {
    /** @var \Tests\TestCase $this 
     * @var User $admin
    */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(5)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [['id', 'name', 'email', 'role']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJson(['success' => true]);
});

test('MANAGER pode listar usuários', function () {

    /** @var \Tests\TestCase $this 
     * @var User $manager
    */

    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->getJson('/api/users')
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('listagem de usuários é paginada', function () {

    /** @var \Tests\TestCase $this 
     * @var User $admin
    */

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(20)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/users');

    $response->assertStatus(200);
    $meta = $response->json('meta');
    expect($meta['per_page'])->toBe(15);
    expect($meta['total'])->toBe(21);
});

test('visualizar usuário requer autenticação', function () {
    
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $this->getJson("/api/users/{$user->id}")->assertStatus(401);
});

test('usuário com role USER não pode visualizar outro usuário', function () {
    
    /** @var \Tests\TestCase $this 
     * @var User $user
    */

    $user   = User::factory()->create(['role' => UserRole::User]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/users/{$target->id}")
        ->assertStatus(403);
});

test('ADMIN pode visualizar qualquer usuário', function () {
    
    /** @var \Tests\TestCase $this 
     * @var User $admin
    */

    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($admin)
        ->getJson("/api/users/{$target->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['name', 'email', 'role', 'email_verified_at', 'created_at'],
        ])
        ->assertJson([
            'success' => true,
            'data'    => [
                'name'  => $target->name,
                'email' => $target->email,
                'role'  => $target->role->value,
            ],
        ]);
});

test('MANAGER pode visualizar qualquer usuário', function () {
    
    /** @var \Tests\TestCase $this 
     * @var User $manager
    */

    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $target  = User::factory()->create();

    $this->actingAs($manager)
        ->getJson("/api/users/{$target->id}")
        ->assertStatus(200);
});

test('FINANCE pode visualizar qualquer usuário', function () {
    
    /** @var \Tests\TestCase $this
     * @var User $finance
     */

    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $target  = User::factory()->create();

    $this->actingAs($finance)
        ->getJson("/api/users/{$target->id}")
        ->assertStatus(200);
});

test('retorna 404 para usuário inexistente', function () {
    
    /** @var \Tests\TestCase $this 
     * @var User $admin
    */

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->getJson('/api/users/99999')
        ->assertStatus(404);
});
