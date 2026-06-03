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
            'employee_nos.*' => ['required', 'distinct', 'exists:employee_information,employee_no'],

            'n_taxation' => ['required', 'array'],
            'n_taxation.Year' => ['required', 'integer', 'digits:4', 'min:1900', 'max:9999'],

            'n_taxation_settings' => ['required', 'array'],
            'n_taxation_settings.train_law_id' => ['required', 'integer', 'exists:train_law,id'],

            'n_taxation_settings.bonuses' => ['nullable', 'array'],
            'n_taxation_settings.bonuses.*.government_bonus_id' => ['required', 'distinct', 'integer', 'exists:government_bonus_types,id'],

            'n_taxation_settings.portion' => ['required', 'array'],
            'n_taxation_settings.portion.salary' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.portion.hazard_pay' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.portion.longevity' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $employeeNos = collect((array) $this->input('employee_nos', []))
            ->map(fn ($employeeNo) => trim((string) $employeeNo))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $bonuses = collect((array) $this->input('n_taxation_settings.bonuses', []))
            ->map(function ($bonus) {
                return [
                    'government_bonus_id' => (int) data_get($bonus, 'government_bonus_id', 0),
                ];
            })
            ->filter(fn (array $bonus) => $bonus['government_bonus_id'] > 0)
            ->unique('government_bonus_id')
            ->values()
            ->all();

        $this->merge([
            'employee_nos' => $employeeNos,
            'n_taxation_settings' => array_merge(
                (array) $this->input('n_taxation_settings', []),
                ['bonuses' => $bonuses],
            ),
        ]);
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
