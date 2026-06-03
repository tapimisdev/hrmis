<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveIndividualTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_nos' => ['required', 'array', 'min:1'],
            'employee_nos.*' => ['required', 'exists:employee_information,employee_no'],

            'n_taxation' => ['required', 'array'],
            'n_taxation.Year' => ['required', 'integer', 'digits:4', 'min:1900', 'max:9999'],

            'n_taxation_settings' => ['required', 'array'],
            'n_taxation_settings.train_law_id' => ['required', 'integer', 'exists:train_law,id'],
            'n_taxation_settings.is_longevity' => ['required', 'boolean'],
            'n_taxation_settings.is_hazard_pay' => ['required', 'boolean'],
            'n_taxation_settings.is_less_bir' => ['required', 'boolean'],

            'n_taxation_settings.bonuses' => ['nullable', 'array'],
            'n_taxation_settings.bonuses.*.government_bonus_id' => ['required', 'integer', 'exists:government_bonus_types,id'],

            'n_taxation_settings.others' => ['nullable', 'array'],
            'n_taxation_settings.others.*.name' => ['required', 'string', 'max:255'],
            'n_taxation_settings.others.*.amount' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.others.*.is_taxable' => ['required', 'boolean'],
            'n_taxation_settings.others.*.is_exempt_bir' => ['required', 'boolean'],

            'n_taxation_settings.portion' => ['required', 'array'],
            'n_taxation_settings.portion.salary' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.portion.hazard_pay' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.portion.longevity' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $portion = (array) $this->input('n_taxation_settings.portion', []);

            if ($portion === []) {
                return;
            }

            $total = (float) ($portion['salary'] ?? 0)
                + (float) ($portion['hazard_pay'] ?? 0)
                + (float) ($portion['longevity'] ?? 0);

            if (abs($total - 100.0) > 0.0001) {
                $validator->errors()->add(
                    'n_taxation_settings.portion',
                    'Salary, hazard pay, and longevity must total exactly 100.'
                );
            }
        });
    }
}
