<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to create a category', function () {
    $user = User::factory()->admin()->create();
    $token = auth('api')->login($user);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/categories', [
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Technology events.',
            'is_active' => true,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Category created successfully.')
        ->assertJsonPath('data.name', 'Technology')
        ->assertJsonPath('data.slug', 'technology')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('categories', [
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);
});

it('rejects category creation without authentication', function () {
    $this->postJson('/api/categories', [
        'name' => 'Technology',
        'slug' => 'technology',
    ])->assertUnauthorized();
});

it('rejects a duplicate category slug', function () {
    Category::factory()->create([
        'slug' => 'technology',
    ]);

    $user = User::factory()->admin()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/categories', [
            'name' => 'Another Technology',
            'slug' => 'technology',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('forbids a regular user from creating a category', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/categories', [
            'name' => 'Technology',
            'slug' => 'technology',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('categories', [
        'slug' => 'technology',
    ]);
});
