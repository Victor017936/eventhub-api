<?php

namespace App\Http\Controllers\Api;

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
