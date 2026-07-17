<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows an upcoming published event from an active category', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    $this
        ->getJson("/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $event->id)
        ->assertJsonPath('data.title', 'Laravel Conference')
        ->assertJsonPath('data.slug', 'laravel-conference')
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.category.name', 'Technology');
});

it('returns not found for a draft event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    $this
        ->getJson("/api/events/{$event->id}")
        ->assertNotFound();
});

it('returns not found for a cancelled event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Cancelled,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    $this
        ->getJson("/api/events/{$event->id}")
        ->assertNotFound();
});

it('returns not found for a past event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHours(2),
    ]);

    $this
        ->getJson("/api/events/{$event->id}")
        ->assertNotFound();
});

it('returns not found when the event category is inactive', function () {
    $category = Category::factory()->create([
        'is_active' => false,
    ]);

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    $this
        ->getJson("/api/events/{$event->id}")
        ->assertNotFound();
});

it('returns not found when the event does not exist', function () {
    $this
        ->getJson('/api/events/999999')
        ->assertNotFound();
});
