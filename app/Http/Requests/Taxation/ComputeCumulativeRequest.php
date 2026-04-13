<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComputeCumulativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taxation_id' => ['required', 'integer', 'exists:taxations,id'],
            'type' => ['required', 'string', Rule::in(['q2', 'q3', 'q4', 'nov', 'final'])],
            'mode' => ['required', 'string', Rule::in(['same_configuration', 'override'])],

            'assumptions' => ['nullable', 'array'],
            'assumptions.basicPay' => ['nullable', 'boolean'],
            'assumptions.midYear' => ['nullable', 'boolean'],
            'assumptions.yearEnd' => ['nullable', 'boolean'],
            'assumptions.longevity' => ['nullable', 'boolean'],
            'assumptions.hazardPay' => ['nullable', 'boolean'],
            'assumptions.lessBirRR32015' => ['nullable', 'boolean'],

            'othersEarnings' => ['nullable', 'array'],
            'othersEarnings.*.name' => ['required_with:othersEarnings', 'string'],
            'othersEarnings.*.tax_type' => [
                'required_with:othersEarnings',
                'string',
                Rule::in(['taxable', 'non_taxable', 'exempt']),
            ],
            'othersEarnings.*.amount' => ['required_with:othersEarnings', 'numeric', 'min:1'],

            'othersDeductions' => ['nullable', 'array'],
            'othersDeductions.*.name' => ['required_with:othersDeductions', 'string'],
            'othersDeductions.*.amount' => ['required_with:othersDeductions', 'numeric', 'min:1'],
        ];
    }
}
