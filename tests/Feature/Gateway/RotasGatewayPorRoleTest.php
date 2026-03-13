<?php

use App\Enums\UserRole;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('rotas de gateway requerem autenticação', function () {
    /** @var TestCase $this */
    $gateway = Gateway::factory()->create();

    $this->patchJson("/api/v1/gateways/{$gateway->id}/status", ['is_active' => false])
        ->assertStatus(401);

    $this->patchJson("/api/v1/gateways/{$gateway->id}/priority", ['priority' => 5])
        ->assertStatus(401);
});

test('ADMIN pode atualizar status e prioridade de gateway', function () {
    /** @var TestCase $this 
     * @var User $admin
    */
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $gateway = Gateway::factory()->create(['is_active' => true, 'priority' => 1]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/gateways/{$gateway->id}/status", ['is_active' => false])
        ->assertStatus(200)
        ->assertJsonPath('data.is_active', false);

    $this->actingAs($admin)
        ->patchJson("/api/v1/gateways/{$gateway->id}/priority", ['priority' => 7])
        ->assertStatus(200)
        ->assertJsonPath('data.priority', 7);
});

test('somente ADMIN pode alterar status e prioridade de gateway', function (UserRole $role) {
    /** @var TestCase $this 
     * @var User $user
    */
    $user = User::factory()->create(['role' => $role]);
    $gateway = Gateway::factory()->create(['is_active' => true, 'priority' => 1]);

    $this->actingAs($user)
        ->patchJson("/api/v1/gateways/{$gateway->id}/status", ['is_active' => false])
        ->assertStatus(403);

    $this->actingAs($user)
        ->patchJson("/api/v1/gateways/{$gateway->id}/priority", ['priority' => 3])
        ->assertStatus(403);
})->with([
    UserRole::Manager,
    UserRole::Finance,
    UserRole::User,
]);
