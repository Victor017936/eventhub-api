<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout successful.');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me')
        ->assertUnauthorized();
});

it('rejects logout without a token', function () {
    $this->postJson('/api/logout')
        ->assertUnauthorized();
});
