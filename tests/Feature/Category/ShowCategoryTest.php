<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows an active category', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $this
        ->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', 'Technology')
        ->assertJsonPath('data.slug', 'technology')
        ->assertJsonPath('data.is_active', true);
});

it('returns not found for an inactive category', function () {
    $category = Category::factory()->create([
        'is_active' => false,
    ]);

    $this
        ->getJson("/api/categories/{$category->id}")
        ->assertNotFound();
});

it('returns not found when the category does not exist', function () {
    $this
        ->getJson('/api/categories/999999')
        ->assertNotFound();
});
