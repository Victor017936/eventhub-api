<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to create an event', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'A conference about Laravel.',
            'location' => 'Chisinau',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'booking_starts_at' => now()->addDay()->toDateTimeString(),
            'booking_ends_at' => now()->addDays(9)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'published',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Event created successfully.')
        ->assertJsonPath('data.title', 'Laravel Conference')
        ->assertJsonPath('data.slug', 'laravel-conference')
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.created_by', $admin->id)
        ->assertJsonPath('data.category.id', $category->id);

    $this->assertDatabaseHas('events', [
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
        'category_id' => $category->id,
        'created_by' => $admin->id,
        'capacity' => 100,
        'status' => 'published',
    ]);
});

it('forbids a regular user from creating an event', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'Description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
        ])
        ->assertForbidden();
});

it('rejects event creation without authentication', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $this
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'Description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
        ])
        ->assertUnauthorized();
});

it('rejects an inactive category', function () {
    $category = Category::factory()->create([
        'is_active' => false,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'Description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('rejects an invalid event date range', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'Description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(9)->toDateTimeString(),
            'capacity' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('rejects a duplicate event slug', function () {
    Event::factory()->create([
        'slug' => 'laravel-conference',
    ]);

    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/events', [
            'category_id' => $category->id,
            'title' => 'Another Conference',
            'slug' => 'laravel-conference',
            'description' => 'Description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});
