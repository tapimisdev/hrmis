<?php

namespace App\Http\Requests\Admin\SalaryPay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => 'required|string|max:50',
            'employment_type_id' => 'required|integer|exists:employment_types,id',
            'cutoff' => [
                'required',
                'string',
                Rule::in(['first_cutoff', 'second_cutoff']),
            ],
            'date' => 'required|date|before_or_equal:today',
        ];
    }
}
