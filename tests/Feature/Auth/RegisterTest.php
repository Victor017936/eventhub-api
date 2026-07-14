<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user and returns a JWT token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Victor Cologhin',
        'email' => 'victor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'User registered successfully.')
        ->assertJsonPath('user.email', 'victor@example.com')
        ->assertJsonPath('authorization.type', 'bearer')
        ->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
            'authorization' => [
                'token',
                'type',
                'expires_in',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Victor Cologhin',
        'email' => 'victor@example.com',
    ]);
});

it('rejects registration when the email already exists', function () {
    $payload = [
        'name' => 'Victor Cologhin',
        'email' => 'victor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->postJson('/api/register', $payload)
        ->assertCreated();

    $this->postJson('/api/register', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
