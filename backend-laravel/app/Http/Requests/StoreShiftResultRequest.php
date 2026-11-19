<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivered_volume' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'volume_unit' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
