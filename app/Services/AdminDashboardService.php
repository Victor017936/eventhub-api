<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function statistics(): array
    {
        $now = now();

        $eventStats = Event::query()
            ->selectRaw('COUNT(*) AS total_count')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS published_count',
                [EventStatus::Published->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND starts_at > ? THEN 1 ELSE 0 END) AS upcoming_count',
                [
                    EventStatus::Published->value,
                    $now,
                ]
            )
            ->first();

        $reservationStats = Reservation::query()
            ->selectRaw('COUNT(*) AS total_count')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS confirmed_count',
                [ReservationStatus::Confirmed->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled_count',
                [ReservationStatus::Cancelled->value]
            )
            ->first();

        $topEvents = Event::query()
            ->leftJoin('reservations', function ($join) {
                $join
                    ->on('events.id', '=', 'reservations.event_id')
                    ->where(
                        'reservations.status',
                        ReservationStatus::Confirmed->value
                    );
            })
            ->select([
                'events.id',
                'events.title',
                'events.capacity',
            ])
            ->selectRaw(
                'COUNT(reservations.id) AS confirmed_reservations'
            )
            ->groupBy(
                'events.id',
                'events.title',
                'events.capacity'
            )
            ->orderByDesc('confirmed_reservations')
            ->orderBy('events.id')
            ->limit(5)
            ->get()
            ->map(function (Event $event): array {
                $confirmedReservations = (int) $event
                    ->confirmed_reservations;

                $capacity = (int) $event->capacity;

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'capacity' => $capacity,
                    'confirmed_reservations' => $confirmedReservations,
                    'occupancy_rate' => $capacity > 0
                        ? round(
                            ($confirmedReservations / $capacity) * 100,
                            2
                        )
                        : 0,
                ];
            })
            ->values()
            ->all();

        return [
            'users' => [
                'total' => User::query()->count(),
            ],
            'categories' => [
                'total' => Category::query()->count(),
                'active' => Category::query()
                    ->where('is_active', true)
                    ->count(),
            ],
            'events' => [
                'total' => (int) ($eventStats?->total_count ?? 0),
                'published' => (int) (
                    $eventStats?->published_count ?? 0
                ),
                'upcoming' => (int) (
                    $eventStats?->upcoming_count ?? 0
                ),
            ],
            'reservations' => [
                'total' => (int) (
                    $reservationStats?->total_count ?? 0
                ),
                'confirmed' => (int) (
                    $reservationStats?->confirmed_count ?? 0
                ),
                'cancelled' => (int) (
                    $reservationStats?->cancelled_count ?? 0
                ),
            ],
            'top_events' => $topEvents,
        ];
    }
}
