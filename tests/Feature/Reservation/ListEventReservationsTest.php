<?php

use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to list reservations for an event', function () {
    $event = Event::factory()->create([
        'title' => 'Laravel Conference',
        'capacity' => 3,
    ]);

    $confirmedUser = User::factory()->create([
        'name' => 'Confirmed User',
        'email' => 'confirmed@example.com',
    ]);

    $cancelledUser = User::factory()->create([
        'name' => 'Cancelled User',
        'email' => 'cancelled@example.com',
    ]);

    $confirmedReservation = Reservation::factory()->create([
        'event_id' => $event->id,
        'user_id' => $confirmedUser->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $cancelledReservation = Reservation::factory()
        ->cancelled()
        ->create([
            'event_id' => $event->id,
            'user_id' => $cancelledUser->id,
        ]);

    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/admin/events/{$event->id}/reservations")
        ->assertOk()
        ->assertJsonPath('event.id', $event->id)
        ->assertJsonPath('event.title', 'Laravel Conference')
        ->assertJsonPath('event.capacity', 3)
        ->assertJsonPath('summary.confirmed', 1)
        ->assertJsonPath('summary.cancelled', 1)
        ->assertJsonPath('summary.available_places', 2)
        ->assertJsonCount(2, 'reservations.data')
        ->assertJsonPath(
            'reservations.data.0.id',
            $cancelledReservation->id
        )
        ->assertJsonPath(
            'reservations.data.0.user.email',
            'cancelled@example.com'
        )
        ->assertJsonPath(
            'reservations.data.1.id',
            $confirmedReservation->id
        )
        ->assertJsonPath('reservations.current_page', 1);
});

it('forbids a regular user from listing event reservations', function () {
    $event = Event::factory()->create();

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/admin/events/{$event->id}/reservations")
        ->assertForbidden();
});

it('rejects event reservation listing without authentication', function () {
    $event = Event::factory()->create();

    $this
        ->getJson("/api/admin/events/{$event->id}/reservations")
        ->assertUnauthorized();
});

it('returns not found when the event does not exist', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/events/999999/reservations')
        ->assertNotFound();
});
