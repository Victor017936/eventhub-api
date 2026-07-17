<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('create', Event::class) ?? false;
    }

    public function rules(): array
    {
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
                'unique:events,slug',
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
                'sometimes',
                Rule::enum(EventStatus::class),
            ],
        ];
    }
}
