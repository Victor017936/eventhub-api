<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only upcoming published events from active categories ordered by start date', function () {
    $activeCategory = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $inactiveCategory = Category::factory()->create([
        'is_active' => false,
    ]);

    $secondEvent = Event::factory()->create([
        'category_id' => $activeCategory->id,
        'title' => 'Second Event',
        'slug' => 'second-event',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    $firstEvent = Event::factory()->create([
        'category_id' => $activeCategory->id,
        'title' => 'First Event',
        'slug' => 'first-event',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $activeCategory->id,
        'status' => EventStatus::Draft,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $activeCategory->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $inactiveCategory->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHours(2),
    ]);

    $response = $this->getJson('/api/events');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $firstEvent->id)
        ->assertJsonPath('data.0.title', 'First Event')
        ->assertJsonPath('data.0.category.name', 'Technology')
        ->assertJsonPath('data.1.id', $secondEvent->id)
        ->assertJsonPath('current_page', 1);
});
