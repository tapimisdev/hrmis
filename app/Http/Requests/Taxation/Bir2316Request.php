<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class Bir2316Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taxable_year' => ['required', 'integer', 'digits:4', 'min:1900', 'max:9999'],
            'all_employees' => ['nullable', 'boolean'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['required', 'integer', 'exists:employee_information,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $employeeIds = collect((array) $this->input('employee_ids', []))
            ->map(fn ($employeeId) => (int) $employeeId)
            ->filter(fn (int $employeeId) => $employeeId > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'taxable_year' => (int) $this->input('taxable_year'),
            'all_employees' => filter_var($this->input('all_employees', false), FILTER_VALIDATE_BOOL),
            'employee_ids' => $employeeIds,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->boolean('all_employees') && empty($this->input('employee_ids', []))) {
                $validator->errors()->add(
                    'employee_ids',
                    'Select at least one employee or choose all employees.'
                );
            }
        });
    }
}
