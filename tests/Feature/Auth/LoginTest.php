<?php

use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login requerido email e senha', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('login requires valid email', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'email-invalido',
        'password' => '123456'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login fails with invalid credentials', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'fake@email.com',
        'password' => '123456'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas'
        ]);
});

test('login fails with wrong password', function () {

    $user = User::factory()->create([
        'password' => Hash::make('123456')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'senha_errada'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas'
        ]);
});

test('login fails when user does not exist', function () {

    $response = $this->postJson('/api/login', [
        'email' => 'naoexiste@email.com',
        'password' => '123456'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas'
        ]);
});

test('user can login and receive token', function () {

    $user = User::factory()->create([
        'password' => Hash::make('123456')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => '123456'
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user',
            'token'
        ]);
});

test('login rate limit', function () {

    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/login', [
            'email' => 'fake@email.com',
            'password' => '123456'
        ]);
    }

    $response->assertStatus(429);
});