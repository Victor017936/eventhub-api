<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to cancel an event', function () {
    $admin = User::factory()->admin()->create();

    $event = Event::factory()->create([
        'created_by' => $admin->id,
        'status' => EventStatus::Published,
    ]);

    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Event cancelled successfully.')
        ->assertJsonPath('data.id', $event->id)
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'status' => 'cancelled',
    ]);
});

it('forbids a regular user from cancelling an event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/events/{$event->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'status' => 'published',
    ]);
});

it('rejects event cancellation without authentication', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
    ]);

    $this
        ->deleteJson("/api/events/{$event->id}")
        ->assertUnauthorized();

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'status' => 'published',
    ]);
});

it('returns not found when cancelling a missing event', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/events/999999')
        ->assertNotFound();
});
