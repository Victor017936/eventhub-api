<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to update a category', function () {
    $category = Category::factory()->create([
        'name' => 'Old Technology',
        'slug' => 'old-technology',
        'description' => 'Old description.',
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Updated description.',
            'is_active' => false,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Category updated successfully.')
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', 'Technology')
        ->assertJsonPath('data.slug', 'technology')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Technology',
        'slug' => 'technology',
        'description' => 'Updated description.',
        'is_active' => false,
    ]);
});

it('allows a category to keep its current slug', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Technology',
            'slug' => 'technology',
        ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'technology');
});

it('forbids a regular user from updating a category', function () {
    $category = Category::factory()->create([
        'name' => 'Original Category',
        'slug' => 'original-category',
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Changed Category',
            'slug' => 'changed-category',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Original Category',
        'slug' => 'original-category',
    ]);
});

it('rejects category update without authentication', function () {
    $category = Category::factory()->create();

    $this
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Technology',
            'slug' => 'technology',
        ])
        ->assertUnauthorized();
});

it('rejects a duplicate slug when updating a category', function () {
    $category = Category::factory()->create([
        'slug' => 'technology',
    ]);

    Category::factory()->create([
        'slug' => 'business',
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Category',
            'slug' => 'business',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('returns not found when updating a missing category', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/categories/999999', [
            'name' => 'Technology',
            'slug' => 'technology',
        ])
        ->assertNotFound();
});
