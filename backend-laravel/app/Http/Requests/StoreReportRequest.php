<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Report::class) ?? false;
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
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ];
    }
}
