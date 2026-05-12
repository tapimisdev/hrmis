<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyForecastToPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taxation_id' => ['required', 'integer', 'exists:taxations,id'],
            'type' => ['required', 'string', Rule::in(['forecast', 'q2', 'q3', 'q4', 'nov', 'final'])],
        ];
    }
}
