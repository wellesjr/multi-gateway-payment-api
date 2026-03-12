<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('excluir usuário requer autenticação', function () {

    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $this->deleteJson("/api/v1/users/{$user->id}")->assertStatus(401);
});

test('usuário com role USER não pode excluir usuários', function () {

    /** @var \Tests\TestCase $this 
     * @var User $user
     */

    $user   = User::factory()->create(['role' => UserRole::User]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/users/{$target->id}")
        ->assertStatus(403);
});

test('usuário com role MANAGER não pode excluir usuários', function () {

    /** @var \Tests\TestCase $this 
     * @var User $manager
     */

    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $target  = User::factory()->create();

    $this->actingAs($manager)
        ->deleteJson("/api/v1/users/{$target->id}")
        ->assertStatus(403);
});

test('usuário com role FINANCE não pode excluir usuários', function () {

    /** @var \Tests\TestCase $this 
     * @var User $finance
     */

    $finance = User::factory()->create(['role' => UserRole::Finance]);
    $target  = User::factory()->create();

    $this->actingAs($finance)
        ->deleteJson("/api/v1/users/{$target->id}")
        ->assertStatus(403);
});

test('ADMIN pode excluir outro usuário', function () {

    /** @var \Tests\TestCase $this 
     * @var User $admin
     */

    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$target->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Usuário excluído com sucesso.',
        ]);

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('ADMIN não pode excluir a si mesmo', function () {

    /** @var \Tests\TestCase $this 
     * @var User $admin
     */

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$admin->id}")
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Você não pode excluir sua própria conta.',
        ]);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('excluir usuário remove seus tokens', function () {

    /** @var \Tests\TestCase $this 
     * @var User $admin
     */

    $admin  = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();
    $target->createToken('test-token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $target->id,
    ]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$target->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $target->id,
    ]);
});

test('excluir usuário inexistente retorna 404', function () {

    /** @var \Tests\TestCase $this 
     *  @var User $admin
     */

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/users/99999')
        ->assertStatus(404);
});
