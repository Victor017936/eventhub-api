<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters events using a search term', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $matchingEvent = Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'Laravel Conference',
        'description' => 'A conference about PHP.',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'JavaScript Conference',
        'description' => 'A frontend conference.',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(6),
        'ends_at' => now()->addDays(6)->addHours(2),
    ]);

    $this
        ->getJson('/api/events?search=Laravel')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingEvent->id)
        ->assertJsonPath('data.0.title', 'Laravel Conference');
});

it('filters events by category and location', function () {
    $technology = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $business = Category::factory()->create([
        'name' => 'Business',
        'slug' => 'business',
        'is_active' => true,
    ]);

    $matchingEvent = Event::factory()->create([
        'category_id' => $technology->id,
        'location' => 'Chisinau Arena',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $technology->id,
        'location' => 'Balti',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(6),
        'ends_at' => now()->addDays(6)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $business->id,
        'location' => 'Chisinau',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(7),
        'ends_at' => now()->addDays(7)->addHours(2),
    ]);

    $url = "/api/events?category_id={$technology->id}&location=Chisinau";

    $this
        ->getJson($url)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingEvent->id);
});

it('filters events by start date interval', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $matchingEvent = Event::factory()->create([
        'category_id' => $category->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $category->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(15),
        'ends_at' => now()->addDays(15)->addHours(2),
    ]);

    $dateFrom = now()->addDays(4)->toDateString();
    $dateTo = now()->addDays(6)->toDateString();

    $this
        ->getJson("/api/events?date_from={$dateFrom}&date_to={$dateTo}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingEvent->id);
});

it('validates the event filters', function () {
    $this
        ->getJson(
            '/api/events?date_from=2030-10-10&date_to=2030-10-01&per_page=100'
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'date_to',
            'per_page',
        ]);
});
