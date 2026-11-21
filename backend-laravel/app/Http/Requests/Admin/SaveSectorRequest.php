<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSectorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['coordinatorIds' => $this->input('coordinatorIds', [])]);
    }

    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->hasRole(Role::ADMIN);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('sectors', 'name')->ignore($this->route('sector')?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'coordinatorIds' => ['sometimes', 'array'],
            'coordinatorIds.*' => ['integer', Rule::exists('user_accounts', 'id')],
        ];
    }
}
