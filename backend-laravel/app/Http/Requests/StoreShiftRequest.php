<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) || $this->user()?->hasRole(Role::COORDINATOR);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sector_id' => ['required', 'integer', 'exists:sectors,id'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:user_accounts,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resource_ids' => ['required', 'array', 'min:1'],
            'resource_ids.*' => ['required', 'integer', 'distinct', 'exists:resources,id'],
        ];
    }
}
