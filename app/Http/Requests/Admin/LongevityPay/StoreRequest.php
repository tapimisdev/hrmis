<?php

namespace App\Http\Requests\Admin\LongevityPay;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:50',
            'month' => 'required|date',
            'employment_type_id' => 'required',
            'employees' => ['required', 'array'],
            'approved_by' => ['required', 'array'],
            'approved_by.*' => ['array', 'min:1'],
            'approved_by.*.*' => ['integer', 'exists:users,id'],
        ];
    }
}
