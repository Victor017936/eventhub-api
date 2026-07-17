<?php

use App\Enums\EventStatus;
use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to reserve an available event', function () {
    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->subDay(),
        'booking_ends_at' => now()->addDays(9),
        'capacity' => 100,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Reservation created successfully.'
        )
        ->assertJsonPath('data.event_id', $event->id)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.event.id', $event->id);

    $this->assertDatabaseHas('reservations', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);
});

it('rejects reservation without authentication', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    $this
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnauthorized();
});

it('rejects a duplicate confirmed reservation', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->subDay(),
        'booking_ends_at' => now()->addDays(9),
    ]);

    $user = User::factory()->create();

    Reservation::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reservation']);

    $this->assertDatabaseCount('reservations', 1);
});

it('rejects a reservation when the event is full', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->subDay(),
        'booking_ends_at' => now()->addDays(9),
        'capacity' => 1,
    ]);

    Reservation::factory()->create([
        'event_id' => $event->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['event']);
});

it('rejects a reservation for an unpublished event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['event']);
});

it('rejects a reservation before the booking period starts', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->addDay(),
        'booking_ends_at' => now()->addDays(9),
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['event']);
});

it('rejects a reservation after the booking period ends', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->subDays(5),
        'booking_ends_at' => now()->subDay(),
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['event']);
});

it('reactivates a cancelled reservation when a place is available', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
        'booking_starts_at' => now()->subDay(),
        'booking_ends_at' => now()->addDays(9),
        'capacity' => 10,
    ]);

    $user = User::factory()->create();

    $reservation = Reservation::factory()
        ->cancelled()
        ->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Reservation reactivated successfully.'
        )
        ->assertJsonPath('data.id', $reservation->id)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.cancelled_at', null);

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);

    $this->assertDatabaseCount('reservations', 1);
});
