<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in a user with valid credentials', function () {
    User::factory()->create([
        'email' => 'victor@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'victor@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('user.email', 'victor@example.com')
        ->assertJsonPath('authorization.type', 'bearer')
        ->assertJsonStructure([
            'message',
            'user',
            'authorization' => [
                'token',
                'type',
                'expires_in',
            ],
        ]);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'victor@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'victor@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid credentials.');
});
