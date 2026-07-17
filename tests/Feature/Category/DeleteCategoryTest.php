<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to deactivate a category', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Category deactivated successfully.')
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Technology',
        'is_active' => false,
    ]);
});

it('forbids a regular user from deactivating a category', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/categories/{$category->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'is_active' => true,
    ]);
});

it('rejects category deactivation without authentication', function () {
    $category = Category::factory()->create();

    $this
        ->deleteJson("/api/categories/{$category->id}")
        ->assertUnauthorized();
});

it('returns not found when deactivating a missing category', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/categories/999999')
        ->assertNotFound();
});
