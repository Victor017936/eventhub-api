<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks past published events as completed', function () {
    $pastPublishedEvent = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    $futurePublishedEvent = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
    ]);

    $pastDraftEvent = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    $pastCancelledEvent = Event::factory()->create([
        'status' => EventStatus::Cancelled,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    $this
        ->artisan('events:complete')
        ->expectsOutput('Completed 1 event(s).')
        ->assertSuccessful();

    expect($pastPublishedEvent->refresh()->status)
        ->toBe(EventStatus::Completed);

    expect($futurePublishedEvent->refresh()->status)
        ->toBe(EventStatus::Published);

    expect($pastDraftEvent->refresh()->status)
        ->toBe(EventStatus::Draft);

    expect($pastCancelledEvent->refresh()->status)
        ->toBe(EventStatus::Cancelled);
});

it('completes no events when there are no eligible events', function () {
    Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
    ]);

    $this
        ->artisan('events:complete')
        ->expectsOutput('Completed 0 event(s).')
        ->assertSuccessful();
});
