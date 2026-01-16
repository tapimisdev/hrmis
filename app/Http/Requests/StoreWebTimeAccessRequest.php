<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebTimeAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_nos' => collect($this->input('employee_nos', []))
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all(),

            'days_of_week' => collect($this->input('days_of_week', []))
                ->map(fn ($d) => ucfirst(strtolower(trim((string) $d)))) // "wed " → "Wed"
                ->values()
                ->all(),

            'specific_dates' => collect($this->input('specific_dates', []))
                ->map(fn ($d) => trim((string) $d))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'employee_nos'   => ['required', 'array', 'min:1'],
            'employee_nos.*' => ['required', 'exists:employee_information,employee_no'],

            'type' => ['required', Rule::in(['always', 'days_of_week', 'specific_dates'])],

            'days_of_week'   => ['nullable', 'array'],
            'days_of_week.*' => [
                Rule::requiredIf($this->type === 'days_of_week'),
                Rule::in(['Mon','Tue','Wed','Thu','Fri','Sat','Sun']),
            ],

            'specific_dates'   => ['nullable', 'array'],
            'specific_dates.*' => [
                Rule::requiredIf($this->type === 'specific_dates'),
                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_nos.required' => 'Please select at least one employee.',
            'employee_nos.min'      => 'Please select at least one employee.',

            'type.required' => 'Please choose a schedule type.',

            'days_of_week.*.in' => 'Invalid day selected.',
            'days_of_week.*.required' => 'Please select at least one day.',

            'specific_dates.*.required' => 'Please add at least one date.',
            'specific_dates.*.date_format' => 'Dates must be in YYYY-MM-DD format.',
        ];
    }
}
