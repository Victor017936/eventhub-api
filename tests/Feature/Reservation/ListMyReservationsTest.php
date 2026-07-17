<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only the authenticated user reservations', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $firstEvent = Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    $secondEvent = Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'PHP Meetup',
        'slug' => 'php-meetup',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(15),
        'ends_at' => now()->addDays(15)->addHours(2),
    ]);

    $otherEvent = Event::factory()->create([
        'category_id' => $category->id,
    ]);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $confirmedReservation = Reservation::factory()->create([
        'event_id' => $firstEvent->id,
        'user_id' => $user->id,
    ]);

    $cancelledReservation = Reservation::factory()
        ->cancelled()
        ->create([
            'event_id' => $secondEvent->id,
            'user_id' => $user->id,
        ]);

    Reservation::factory()->create([
        'event_id' => $otherEvent->id,
        'user_id' => $otherUser->id,
    ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/my-reservations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $cancelledReservation->id)
        ->assertJsonPath('data.0.status', 'cancelled')
        ->assertJsonPath('data.0.event.id', $secondEvent->id)
        ->assertJsonPath(
            'data.0.event.category.name',
            'Technology'
        )
        ->assertJsonPath('data.1.id', $confirmedReservation->id)
        ->assertJsonPath('data.1.status', 'confirmed')
        ->assertJsonPath('data.1.event.id', $firstEvent->id)
        ->assertJsonPath('current_page', 1);
});

it('rejects reservation listing without authentication', function () {
    $this
        ->getJson('/api/my-reservations')
        ->assertUnauthorized();
});
