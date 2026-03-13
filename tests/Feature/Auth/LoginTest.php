<?php

use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('login requerido email e senha', function () {

    /** @var \Tests\TestCase $this */

    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('login falha com email inválido', function () {

    /** @var \Tests\TestCase $this */

    $response = $this->postJson('/api/login', [
        'email' => 'email-invalido',
        'password' => '123456'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login falha com credenciais inválidas', function () {

    /** @var \Tests\TestCase $this */

    $response = $this->postJson('/api/login', [
        'email' => 'fake@email.com',
        'password' => '123456'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas'
        ]);
});

test('login falha com senha incorreta', function () {

    /** @var \Tests\TestCase $this */

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

test('login falha quando usuário não existe', function () {

    /** @var \Tests\TestCase $this */

    $response = $this->postJson('/api/login', [
        'email' => 'naoexiste@email.com',
        'password' => '123456'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas'
        ]);
});

test('login com credenciais válidas', function () {

    /** @var \Tests\TestCase $this */

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

test('login com limite de tentativas atingido', function () {

    /** @var \Tests\TestCase $this */

    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/login', [
            'email' => 'fake@email.com',
            'password' => '123456'
        ]);
    }

    $response->assertStatus(429);
});

test('login cria token sanctum com expiração de 5 minutos', function () {

    /** @var \Tests\TestCase $this */

    config(['sanctum.expiration' => 5]);

    $user = User::factory()->create([
        'password' => Hash::make('123456'),
    ]);

    $before = now();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => '123456',
    ]);

    $after = now();

    $response->assertStatus(200);

    $plainTextToken = (string) $response->json('token');
    [$tokenId] = explode('|', $plainTextToken, 2);

    $token = PersonalAccessToken::query()->find((int) $tokenId);

    expect($token)->not->toBeNull();
    expect($token?->expires_at)->not->toBeNull();

    $expectedMin = $before->copy()->addMinutes(5)->subSeconds(5);
    $expectedMax = $after->copy()->addMinutes(5)->addSeconds(5);

    expect($token->expires_at->between($expectedMin, $expectedMax))->toBeTrue();
});
