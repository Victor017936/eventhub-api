<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminIndexEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can(
            'viewAny',
            Event::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'status' => [
                'nullable',
                Rule::enum(EventStatus::class),
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }
}
