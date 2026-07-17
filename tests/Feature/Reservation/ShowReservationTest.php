<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to view their own reservation', function () {
    $category = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
    ]);

    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/reservations/{$reservation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reservation->id)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.event.id', $event->id)
        ->assertJsonPath('data.event.title', 'Laravel Conference')
        ->assertJsonPath(
            'data.event.category.name',
            'Technology'
        );
});

it('forbids a user from viewing another user reservation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $owner->id,
    ]);

    $token = auth('api')->login($otherUser);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/reservations/{$reservation->id}")
        ->assertForbidden();
});

it('rejects reservation details without authentication', function () {
    $reservation = Reservation::factory()->create();

    $this
        ->getJson("/api/reservations/{$reservation->id}")
        ->assertUnauthorized();
});

it('returns not found when the reservation does not exist', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/reservations/999999')
        ->assertNotFound();
});
