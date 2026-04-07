<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
