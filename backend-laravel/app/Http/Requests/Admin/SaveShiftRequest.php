<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->hasRole(Role::ADMIN);
    }

    public function rules(): array
    {
        return [
            'sectorId' => ['required', 'integer', Rule::exists('sectors', 'id')->where('status', 'active')],
            'responsibleUserId' => ['nullable', 'integer', Rule::exists('user_accounts', 'id')],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resourceIds' => ['required', 'array', 'min:1'],
            'resourceIds.*' => ['required', 'integer', 'distinct', Rule::exists('resources', 'id')],
        ];
    }
}
