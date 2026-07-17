<?php

use App\Enums\EventStatus;
use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to view dashboard statistics', function () {
    $admin = User::factory()->admin()->create();

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $thirdUser = User::factory()->create();

    $activeCategory = Category::factory()->create([
        'name' => 'Technology',
        'slug' => 'technology',
        'is_active' => true,
    ]);

    Category::factory()->create([
        'name' => 'Inactive category',
        'slug' => 'inactive-category',
        'is_active' => false,
    ]);

    $firstEvent = Event::factory()->create([
        'category_id' => $activeCategory->id,
        'created_by' => $admin->id,
        'title' => 'Laravel Conference',
        'slug' => 'laravel-conference',
        'capacity' => 4,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(2),
    ]);

    $secondEvent = Event::factory()->create([
        'category_id' => $activeCategory->id,
        'created_by' => $admin->id,
        'title' => 'PHP Meetup',
        'slug' => 'php-meetup',
        'capacity' => 2,
        'status' => EventStatus::Published,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);

    Event::factory()->create([
        'category_id' => $activeCategory->id,
        'created_by' => $admin->id,
        'title' => 'Completed Event',
        'slug' => 'completed-event',
        'capacity' => 10,
        'status' => EventStatus::Completed,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    Reservation::factory()->create([
        'event_id' => $firstEvent->id,
        'user_id' => $firstUser->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    Reservation::factory()->create([
        'event_id' => $firstEvent->id,
        'user_id' => $secondUser->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    Reservation::factory()
        ->cancelled()
        ->create([
            'event_id' => $firstEvent->id,
            'user_id' => $thirdUser->id,
        ]);

    Reservation::factory()->create([
        'event_id' => $secondEvent->id,
        'user_id' => $thirdUser->id,
        'status' => ReservationStatus::Confirmed,
    ]);

    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.users.total', 4)
        ->assertJsonPath('data.categories.total', 2)
        ->assertJsonPath('data.categories.active', 1)
        ->assertJsonPath('data.events.total', 3)
        ->assertJsonPath('data.events.published', 2)
        ->assertJsonPath('data.events.upcoming', 2)
        ->assertJsonPath('data.reservations.total', 4)
        ->assertJsonPath('data.reservations.confirmed', 3)
        ->assertJsonPath('data.reservations.cancelled', 1)
        ->assertJsonPath(
            'data.top_events.0.id',
            $firstEvent->id
        )
        ->assertJsonPath(
            'data.top_events.0.confirmed_reservations',
            2
        )
        ->assertJsonPath(
            'data.top_events.0.occupancy_rate',
            50
        )
        ->assertJsonPath(
            'data.top_events.1.id',
            $secondEvent->id
        )
        ->assertJsonPath(
            'data.top_events.1.confirmed_reservations',
            1
        );
});

it('forbids a regular user from viewing the dashboard', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/admin/dashboard')
        ->assertForbidden();
});

it('rejects dashboard access without authentication', function () {
    $this
        ->getJson('/api/admin/dashboard')
        ->assertUnauthorized();
});
