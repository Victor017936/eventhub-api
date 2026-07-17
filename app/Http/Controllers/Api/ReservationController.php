<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api');

        $reservations = $user
            ->reservations()
            ->with([
                'event:id,category_id,title,slug,location,starts_at,ends_at,status',
                'event.category:id,name,slug',
            ])
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($reservations);
    }

    public function adminIndex(Event $event): JsonResponse
    {
        Gate::authorize('viewReservations', $event);

        $event->loadCount([
            'reservations as confirmed_reservations_count' => function ($query) {
                $query->where(
                    'status',
                    ReservationStatus::Confirmed->value
                );
            },
            'reservations as cancelled_reservations_count' => function ($query) {
                $query->where(
                    'status',
                    ReservationStatus::Cancelled->value
                );
            },
        ]);

        $reservations = $event
            ->reservations()
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'capacity' => $event->capacity,
            ],
            'summary' => [
                'confirmed' => $event->confirmed_reservations_count,
                'cancelled' => $event->cancelled_reservations_count,
                'available_places' => max(
                    0,
                    $event->capacity
                        - $event->confirmed_reservations_count
                ),
            ],
            'reservations' => $reservations,
        ]);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        Gate::authorize('view', $reservation);

        $reservation->load([
            'event:id,category_id,title,slug,location,starts_at,ends_at,status',
            'event.category:id,name,slug',
        ]);

        return response()->json([
            'data' => $reservation,
        ]);
    }

    public function destroy(
        Reservation $reservation,
        ReservationService $reservationService
    ): JsonResponse {
        Gate::authorize('delete', $reservation);

        $reservation = $reservationService->cancel($reservation);

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'data' => $reservation,
        ]);
    }

    public function store(
        StoreReservationRequest $request,
        Event $event,
        ReservationService $reservationService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user('api');

        $reservation = $reservationService->reserve($event, $user);

        $created = $reservation->wasRecentlyCreated;

        return response()->json([
            'message' => $created
                ? 'Reservation created successfully.'
                : 'Reservation reactivated successfully.',
            'data' => $reservation,
        ], $created ? 201 : 200);
    }
}
