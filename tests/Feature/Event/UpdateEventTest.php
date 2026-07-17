<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to update an event', function () {
    $oldCategory = Category::factory()->create([
        'is_active' => true,
    ]);

    $newCategory = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();

    $event = Event::factory()->create([
        'category_id' => $oldCategory->id,
        'created_by' => $admin->id,
        'title' => 'Old Event',
        'slug' => 'old-event',
        'status' => EventStatus::Draft,
    ]);

    $token = auth('api')->login($admin);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $newCategory->id,
            'title' => 'Laravel Conference',
            'slug' => 'laravel-conference',
            'description' => 'Updated event description.',
            'location' => 'Chisinau',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'booking_starts_at' => now()->addDay()->toDateTimeString(),
            'booking_ends_at' => now()->addDays(9)->toDateTimeString(),
            'capacity' => 200,
            'status' => 'published',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Event updated successfully.')
        ->assertJsonPath('data.id', $event->id)
        ->assertJsonPath('data.title', 'Laravel Conference')
        ->assertJsonPath('data.slug', 'laravel-conference')
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.category.id', $newCategory->id)
        ->assertJsonPath('data.created_by', $admin->id);

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'category_id' => $newCategory->id,
        'created_by' => $admin->id,
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
        'capacity' => 200,
        'status' => 'published',
    ]);
});

it('allows an event to keep its current slug', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'created_by' => $admin->id,
        'slug' => 'laravel-conference',
    ]);

    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $category->id,
            'title' => 'Updated Conference',
            'slug' => 'laravel-conference',
            'description' => 'Updated description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'laravel-conference');
});

it('forbids a regular user from updating an event', function () {
    $event = Event::factory()->create();

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $event->category_id,
            'title' => 'Changed Event',
            'slug' => 'changed-event',
            'description' => 'Changed description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertForbidden();
});

it('rejects event update without authentication', function () {
    $event = Event::factory()->create();

    $this
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $event->category_id,
            'title' => 'Changed Event',
            'slug' => 'changed-event',
            'description' => 'Changed description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertUnauthorized();
});

it('rejects a duplicate slug when updating an event', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'created_by' => $admin->id,
        'slug' => 'first-event',
    ]);

    Event::factory()->create([
        'slug' => 'second-event',
    ]);

    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $category->id,
            'title' => 'Updated Event',
            'slug' => 'second-event',
            'description' => 'Updated description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('rejects an inactive category when updating an event', function () {
    $inactiveCategory = Category::factory()->create([
        'is_active' => false,
    ]);

    $admin = User::factory()->admin()->create();

    $event = Event::factory()->create([
        'created_by' => $admin->id,
    ]);

    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/events/{$event->id}", [
            'category_id' => $inactiveCategory->id,
            'title' => 'Updated Event',
            'slug' => 'updated-event',
            'description' => 'Updated description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('returns not found when updating a missing event', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/events/999999', [
            'category_id' => $category->id,
            'title' => 'Updated Event',
            'slug' => 'updated-event',
            'description' => 'Updated description.',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ])
        ->assertNotFound();
});
