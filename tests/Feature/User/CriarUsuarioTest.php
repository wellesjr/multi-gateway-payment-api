<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('criar usuário requer autenticação', function () {

    /** @var TestCase $this */
    $this->postJson('/api/users', [])->assertStatus(401);
});

test('usuário com role USER não pode criar usuários', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->postJson('/api/users', [
            'name' => 'Novo User',
            'email' => 'novo@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ])
        ->assertStatus(403);
});

test('usuário com role FINANCE não pode criar usuários', function () {

    /** @var TestCase $this
     * @var User $user
     */
    $user = User::factory()->create(['role' => UserRole::Finance]);

    $this->actingAs($user)
        ->postJson('/api/users', [
            'name' => 'Novo User',
            'email' => 'novo@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ])
        ->assertStatus(403);
});

test('ADMIN pode criar usuário com sucesso', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'data' => [
                'name' => 'Novo Usuário',
                'email' => 'novo@email.com',
            ],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'novo@email.com']);
});

test('MANAGER pode criar usuário com sucesso', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $response = $this->actingAs($manager)
        ->postJson('/api/users', [
            'name' => 'Criado por Manager',
            'email' => 'manager-criou@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'manager-criou@email.com']);
});

test('ADMIN pode criar usuário com role ADMIN', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Novo Admin',
            'email' => 'admin2@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'role' => 'ADMIN',
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', [
        'email' => 'admin2@email.com',
        'role' => 'ADMIN',
    ]);
});

test('MANAGER não pode criar usuário com role ADMIN', function () {

    /** @var TestCase $this
     * @var User $manager
     */
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->postJson('/api/users', [
            'name' => 'Tentativa Admin',
            'email' => 'tentativa@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'role' => 'ADMIN',
        ])
        ->assertStatus(422);
});

test('usuário criado sem role recebe role USER por padrão', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Sem Role',
            'email' => 'semrole@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'semrole@email.com',
        'role' => 'USER',
    ]);
});

test('criar usuário falha sem campos obrigatórios', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('criar usuário falha com email duplicado', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $existing = User::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Duplicado',
            'email' => $existing->email,
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('criar usuário falha com senha curta', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Senha Curta',
            'email' => 'curta@email.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('criar usuário falha sem confirmação de senha', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Sem Confirmação',
            'email' => 'semconfirmacao@email.com',
            'password' => 'senha12345',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('criar usuário falha com role inválido', function () {

    /** @var TestCase $this
     * @var User $admin
     */
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'Role Inválido',
            'email' => 'invalido@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'role' => 'SUPER_ADMIN',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});
