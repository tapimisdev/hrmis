<?php

namespace App\Http\Requests\Admin\SalaryPay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:255'],
            'cutoff' => ['required', 'string', 'in:first_cutoff,second_cutoff'],
            'employment_type_id' => ['required', 'integer', 'exists:employment_types,id'],
            'date' => ['required', 'date'],

            'approved_by' => ['required', 'array'],
            'approved_by.*' => ['array', 'min:1'],
            'approved_by.*.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'The label field is required.',
            'cutoff.in' => 'The cutoff must be either first_cutoff or second_cutoff.',
            'employees.eligible.*.firstname.required' => 'Each eligible employee must have a first name.',
            'employees.eligible.*.lastname.required' => 'Each eligible employee must have a last name.',
        ];
    }
}
