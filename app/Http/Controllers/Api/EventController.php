<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
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

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth('api')->id();

        $event = Event::create($data);

        $event->load('category:id,name,slug');

        return response()->json([
            'message' => 'Event created successfully.',
            'data' => $event,
        ], 201);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event
    ): JsonResponse {
        $event->update($request->validated());

        $event->refresh();
        $event->load('category:id,name,slug');

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => $event,
        ]);
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
