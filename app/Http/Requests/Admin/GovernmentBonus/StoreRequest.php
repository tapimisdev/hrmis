<?php

namespace App\Http\Requests\Admin\GovernmentBonus;

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
            'employment_type_id' => 'required|integer',
            'government_bonus_type_id' => 'required|integer|exists:government_bonus_types,id',
            'employees' => ['required', 'array'],
            'approved_by' => ['required', 'array'],
            'approved_by.*' => ['array', 'min:1'],
            'approved_by.*.*' => ['integer', 'exists:users,id'],
        ];
    }
}
