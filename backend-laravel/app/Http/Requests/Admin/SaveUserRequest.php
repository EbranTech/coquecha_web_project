<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['sectorIds' => $this->input('sectorIds', [])]);
    }

    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()?->hasRole(Role::ADMIN);
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:80'], 'lastName' => ['required', 'string', 'max:80'],
            'documentNumber' => ['required', 'string', 'max:14', Rule::unique('people', 'document_number')->ignore($this->route('user')?->person_id)],
            'phone' => ['nullable', 'string', 'max:9'], 'email' => ['nullable', 'email', 'max:120'],
            'username' => ['required', 'string', 'max:60', Rule::unique('user_accounts', 'username')->ignore($this->route('user')?->id)],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'min:8', 'max:128'],
            'roleName' => ['required', Rule::in([Role::ADMIN, Role::COORDINATOR, Role::NEIGHBOR])],
            'isActive' => ['required', 'boolean'], 'sectorIds' => ['sometimes', 'array'], 'sectorIds.*' => ['integer', Rule::exists('sectors', 'id')],
        ];
    }
}
