<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->with('category:id,name,slug')
            ->where('status', EventStatus::Published->value)
            ->where('starts_at', '>=', now())
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('starts_at')
            ->paginate(10);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
{
    $event->load('category:id,name,slug,is_active');

    abort_if(
        $event->status !== EventStatus::Published
        || $event->starts_at->isPast()
        || ! $event->category->is_active,
        404
    );

    return response()->json([
        'data' => $event,
    ]);
}
}
