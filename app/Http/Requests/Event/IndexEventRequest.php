<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'date_from' => [
                'nullable',
                'required_with:date_to',
                'date',
            ],
            'date_to' => [
                'nullable',
                'required_with:date_from',
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
