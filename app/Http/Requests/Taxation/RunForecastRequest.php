<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RunForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            
            // 'year' => ['required', 'numeric', 'unique:taxations,year'],
            'year' => ['required', 'numeric'],

            // =========================
            // Tax settings (TAB A)
            // =========================
            'hazardTaxId'     => ['required', 'integer', 'exists:payroll_components,id'],
            'salaryTaxId'     => ['required', 'integer', 'exists:payroll_components,id'],
            'longevityTaxId'  => ['required', 'integer', 'exists:payroll_components,id'],
            'trainLawId'      => ['required', 'integer', 'exists:train_law,id'],

            // =========================
            // Assumptions (TAB B)
            // =========================
            'assumptions'                   => ['required', 'array'],
            'assumptions.basicPay'          => ['required', 'boolean'],
            'assumptions.midYear'           => ['required', 'boolean'],
            'assumptions.yearEnd'           => ['required', 'boolean'],
            'assumptions.longevity'         => ['required', 'boolean'],
            'assumptions.hazardPay'         => ['required', 'boolean'],
            'assumptions.lessBirRR32015'  => ['required', 'boolean'],

            // =========================
            // Earnings (TAB B)
            // =========================
            'othersEarnings'                => ['nullable', 'array'],
            'othersEarnings.*.name'         => ['required_with:earnings.others', 'string'],
            'othersEarnings.*.tax_type' => [
                'required_with:earnings.others',
                'string',
                Rule::in(['taxable', 'non_taxable', 'exempt']),
            ],
            'othersEarnings.*.amount'       => ['required_with:earnings.others', 'numeric', 'min:1'],

            // =========================
            // Deductions (TAB B)
            // =========================
            'deductions'                    => ['required', 'array'],
            'deductions.gsis'               => ['required', 'boolean'],
            'deductions.philhealth'         => ['required', 'boolean'],
            'deductions.pagibig'            => ['required', 'boolean'],

            'othersDeductions'              => ['nullable', 'array'],
            'othersDeductions.*.name'       => ['required_with:deductions.others', 'string'],
            'othersDeductions.*.amount'     => ['required_with:deductions.others', 'numeric', 'min:1'],

            // =========================
            // Allocation (TAB C)
            // =========================
            'allocation'                     => ['required', 'array'],
            'allocation.hazardPayPct'      => ['required', 'numeric', 'min:0', 'max:100'],
            'allocation.basicPayPct'       => ['required', 'numeric', 'min:0', 'max:100'],
            'allocation.longevityPct'       => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator->after(function (Validator $validator) {
            if (!$this->filled('allocation')) {
                return;
            }

            $allocation = (array) $this->input('allocation', []);
            $total = collect($allocation)->sum();

            // Float-safe comparison
            if (abs(((float) $total) - 100.0) > 0.0001) {
                $validator->errors()->add(
                    'allocation',
                    'Allocation percentages must total exactly 100%.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'othersEarnings.*.tax_type.required' =>
                'This is required',
        ];
    }
}
