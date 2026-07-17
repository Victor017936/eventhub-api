<?php

use App\Enums\EventStatus;
use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to cancel their own reservation', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => ReservationStatus::Confirmed,
        'cancelled_at' => null,
    ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reservations/{$reservation->id}")
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Reservation cancelled successfully.'
        )
        ->assertJsonPath('data.id', $reservation->id)
        ->assertJsonPath('data.status', 'cancelled');

    $reservation->refresh();

    expect($reservation->status)
        ->toBe(ReservationStatus::Cancelled);

    expect($reservation->cancelled_at)
        ->not->toBeNull();
});

it('forbids a user from cancelling another user reservation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $owner->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $token = auth('api')->login($otherUser);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reservations/{$reservation->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);
});

it('rejects reservation cancellation without authentication', function () {
    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
    ]);

    $this
        ->deleteJson("/api/reservations/{$reservation->id}")
        ->assertUnauthorized();

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'confirmed',
    ]);
});

it('rejects cancelling an already cancelled reservation', function () {
    $user = User::factory()->create();

    $reservation = Reservation::factory()
        ->cancelled()
        ->create([
            'user_id' => $user->id,
        ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reservations/{$reservation->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reservation']);
});

it('rejects cancelling a reservation for a past event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Completed,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHours(2),
    ]);

    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/reservations/{$reservation->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reservation']);

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'confirmed',
    ]);
});

it('returns not found when cancelling a missing reservation', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/reservations/999999')
        ->assertNotFound();
});
