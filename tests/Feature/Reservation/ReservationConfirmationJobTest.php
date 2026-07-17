<?php

use App\Enums\EventStatus;
use App\Jobs\SendReservationConfirmation;
use App\Mail\ReservationConfirmed;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues a confirmation job after creating a reservation', function () {
    Queue::fake();

    $category = Category::factory()->create([
        'is_active' => true,
    ]);

    $startsAt = now()->addDays(10);

    $event = Event::factory()->create([
        'category_id' => $category->id,
        'status' => EventStatus::Published,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->copy()->addHours(2),
        'booking_starts_at' => now()->subDay(),
        'booking_ends_at' => now()->addDays(5),
        'capacity' => 100,
    ]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/events/{$event->id}/reservations")
        ->assertCreated();

    $reservation = Reservation::query()
        ->where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    Queue::assertPushed(
        SendReservationConfirmation::class,
        function (SendReservationConfirmation $job) use ($reservation) {
            return $job->reservation->id === $reservation->id;
        }
    );
});

it('sends a confirmation email when the job is processed', function () {
    Mail::fake();

    $reservation = Reservation::factory()->create();

    $job = new SendReservationConfirmation($reservation);

    $job->handle();

    Mail::assertSent(
        ReservationConfirmed::class,
        function (ReservationConfirmed $mail) use ($reservation) {
            return $mail->hasTo($reservation->user->email)
                && $mail->reservation->id === $reservation->id;
        }
    );
});
