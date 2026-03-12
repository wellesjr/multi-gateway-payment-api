<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('atualizar usuário requer autenticação', function () {

    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->putJson("/api/users/{$user->id}", ['name' => 'Novo Nome'])
        ->assertStatus(401);
    });

test('usuário com role USER não pode atualizar outro usuário', function () {

    /**
     * @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/users/{$target->id}", ['name' => 'Hackeado'])
        ->assertStatus(403);
    });

test('ADMIN pode atualizar qualquer usuário', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($admin)
        ->putJson("/api/users/{$target->id}", ['name' => 'Nome Atualizado']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'data' => ['name' => 'Nome Atualizado'],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'name' => 'Nome Atualizado',
    ]);
    });

test('MANAGER pode atualizar usuário com role USER', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($manager)
        ->putJson("/api/users/{$target->id}", ['name' => 'Atualizado pelo Manager'])
        ->assertStatus(200);
    });

test('MANAGER não pode atualizar usuário com role ADMIN', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($manager)
        ->putJson("/api/users/{$admin->id}", ['name' => 'Tentativa'])
        ->assertStatus(403);
    });

test('MANAGER não pode atribuir role ADMIN', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($manager)
        ->putJson("/api/users/{$target->id}", ['role' => 'ADMIN'])
        ->assertStatus(422);
    });

test('ADMIN pode alterar role de outro usuário', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($admin)
        ->putJson("/api/users/{$target->id}", ['role' => 'MANAGER']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'role' => 'MANAGER',
    ]);
    });

test('FINANCE não pode atualizar usuários', function () {

    /**  @var TestCase $this
     * @var User $finance
     */
    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $target = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($finance)
        ->putJson("/api/users/{$target->id}", ['name' => 'Tentativa'])
        ->assertStatus(403);
    });

test('USER não pode atualizar usuários', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/users/{$target->id}", ['name' => 'Tentativa'])
        ->assertStatus(403);
    });

test('MANAGER pode atualizar seu próprio nome', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->putJson("/api/users/{$manager->id}", ['name' => 'Meu Novo Nome'])
        ->assertStatus(200)
        ->assertJson(['data' => ['name' => 'Meu Novo Nome']]);
    });

test('atualizar email para um já existente falha', function () {

    /** @var TestCase $this
     * @var User $admin
     * @var User $target
     * @var User $existing
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();
    $existing = User::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/users/{$target->id}", ['email' => $existing->email])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    });

test('atualizar com role inválido falha', function () {
    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();

    /** @var TestCase $this 
     * @var User $admin
    */
    $this->actingAs($admin)
        ->putJson("/api/users/{$target->id}", ['role' => 'INEXISTENTE'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
    });
