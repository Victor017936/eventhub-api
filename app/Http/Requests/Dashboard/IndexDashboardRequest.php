<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class IndexDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user !== null
            && $user->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
