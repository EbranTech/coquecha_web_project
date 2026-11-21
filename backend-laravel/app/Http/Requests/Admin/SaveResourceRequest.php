<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->hasRole(Role::ADMIN);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'resource_type' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:30'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'sectorIds' => ['required', 'array', 'min:1'],
            'sectorIds.*' => ['integer', Rule::exists('sectors', 'id')],
        ];
    }
}
