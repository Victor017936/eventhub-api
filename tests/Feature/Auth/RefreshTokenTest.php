<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refreshes an authenticated user token', function () {
    $user = User::factory()->create();

    $oldToken = auth('api')->login($user);

    $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
        ->postJson('/api/refresh');

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Token refreshed successfully.')
        ->assertJsonPath('authorization.type', 'bearer')
        ->assertJsonStructure([
            'message',
            'authorization' => [
                'token',
                'type',
                'expires_in',
            ],
        ]);

    $newToken = $response->json('authorization.token');

    expect($newToken)
        ->not->toBeNull()
        ->not->toBe($oldToken);

    $this->withHeader('Authorization', "Bearer {$newToken}")
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);
});

it('rejects refresh without a token', function () {
    $this->postJson('/api/refresh')
        ->assertUnauthorized();
});
