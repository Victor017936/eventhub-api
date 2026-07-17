<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user('api')?->can('update', $event) ?? false);
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('events', 'slug')->ignore($event),
            ],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'booking_starts_at' => [
                'nullable',
                'required_with:booking_ends_at',
                'date',
                'before:starts_at',
            ],
            'booking_ends_at' => [
                'nullable',
                'required_with:booking_starts_at',
                'date',
                'after_or_equal:booking_starts_at',
                'before_or_equal:starts_at',
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => [
                'required',
                Rule::enum(EventStatus::class),
            ],
        ];
    }
}
