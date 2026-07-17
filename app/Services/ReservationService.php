<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function reserve(Event $event, User $user): Reservation
    {
        return DB::transaction(function () use ($event, $user) {
            $lockedEvent = Event::query()
                ->with('category:id,is_active')
                ->lockForUpdate()
                ->findOrFail($event->getKey());

            $this->ensureEventCanBeReserved($lockedEvent);

            $existingReservation = Reservation::query()
                ->where('event_id', $lockedEvent->id)
                ->where('user_id', $user->id)
                ->first();

            if (
                $existingReservation?->status
                === ReservationStatus::Confirmed
            ) {
                throw ValidationException::withMessages([
                    'reservation' => [
                        'You already have a confirmed reservation for this event.',
                    ],
                ]);
            }

            $confirmedReservations = Reservation::query()
                ->where('event_id', $lockedEvent->id)
                ->where(
                    'status',
                    ReservationStatus::Confirmed->value
                )
                ->count();

            if ($confirmedReservations >= $lockedEvent->capacity) {
                throw ValidationException::withMessages([
                    'event' => [
                        'This event has no available places.',
                    ],
                ]);
            }

            if ($existingReservation !== null) {
                $existingReservation->update([
                    'status' => ReservationStatus::Confirmed,
                    'cancelled_at' => null,
                ]);

                return $existingReservation
                    ->refresh()
                    ->load('event:id,title,slug,starts_at,capacity,status');
            }

            return Reservation::create([
                'event_id' => $lockedEvent->id,
                'user_id' => $user->id,
                'status' => ReservationStatus::Confirmed,
                'cancelled_at' => null,
            ])->load('event:id,title,slug,starts_at,capacity,status');
        });
    }

    private function ensureEventCanBeReserved(Event $event): void
    {
        if ($event->status !== EventStatus::Published) {
            throw ValidationException::withMessages([
                'event' => [
                    'Only published events can be reserved.',
                ],
            ]);
        }

        if ($event->starts_at->isPast()) {
            throw ValidationException::withMessages([
                'event' => [
                    'Past events cannot be reserved.',
                ],
            ]);
        }

        if (! $event->category->is_active) {
            throw ValidationException::withMessages([
                'event' => [
                    'Events from inactive categories cannot be reserved.',
                ],
            ]);
        }

        if ($event->booking_starts_at?->isFuture()) {
            throw ValidationException::withMessages([
                'event' => [
                    'The reservation period has not started yet.',
                ],
            ]);
        }

        if ($event->booking_ends_at?->isPast()) {
            throw ValidationException::withMessages([
                'event' => [
                    'The reservation period has ended.',
                ],
            ]);
        }
    }
}
