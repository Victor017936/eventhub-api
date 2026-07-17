<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\IndexEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function index(IndexEventRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $events = Event::query()
            ->with('category:id,name,slug')
            ->where('status', EventStatus::Published->value)
            ->where('starts_at', '>=', now())
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['location']), function ($query) use ($filters) {
                $query->where(
                    'location',
                    'like',
                    "%{$filters['location']}%"
                );
            })
            ->when(isset($filters['date_from']), function ($query) use ($filters) {
                $query->whereDate('starts_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($query) use ($filters) {
                $query->whereDate('starts_at', '<=', $filters['date_to']);
            })
            ->orderBy('starts_at')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

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

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $event->update([
            'status' => EventStatus::Cancelled,
        ]);

        return response()->json([
            'message' => 'Event cancelled successfully.',
            'data' => $event->fresh(),
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
