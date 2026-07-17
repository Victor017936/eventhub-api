<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only active categories ordered by name', function () {
    Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    Category::factory()->create([
        'name' => 'Business',
        'slug' => 'business',
        'is_active' => true,
    ]);

    Category::factory()->create([
        'name' => 'Hidden',
        'slug' => 'hidden',
        'is_active' => false,
    ]);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Business')
        ->assertJsonPath('data.1.name', 'Technology');
});
