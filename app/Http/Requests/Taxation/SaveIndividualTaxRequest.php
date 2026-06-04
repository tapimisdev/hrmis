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
            'n_taxation_settings.employee_portions' => ['nullable', 'array'],
            'n_taxation_settings.employee_portions.*' => ['required', 'array'],
            'n_taxation_settings.employee_portions.*.salary' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.employee_portions.*.hazard_pay' => ['required', 'numeric', 'min:0'],
            'n_taxation_settings.employee_portions.*.longevity' => ['required', 'numeric', 'min:0'],
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
        $defaultPortion = [
            'salary' => (float) data_get($this->input('n_taxation_settings', []), 'portion.salary', 80),
            'hazard_pay' => (float) data_get($this->input('n_taxation_settings', []), 'portion.hazard_pay', 20),
            'longevity' => (float) data_get($this->input('n_taxation_settings', []), 'portion.longevity', 0),
        ];
        $employeePortions = collect((array) $this->input('n_taxation_settings.employee_portions', []))
            ->mapWithKeys(function ($portion, $employeeNo) use ($defaultPortion) {
                return [
                    trim((string) $employeeNo) => [
                        'salary' => (float) data_get($portion, 'salary', $defaultPortion['salary']),
                        'hazard_pay' => (float) data_get($portion, 'hazard_pay', $defaultPortion['hazard_pay']),
                        'longevity' => (float) data_get($portion, 'longevity', $defaultPortion['longevity']),
                    ],
                ];
            })
            ->filter(fn (array $portion, string $employeeNo) => $employeeNo !== '')
            ->only($employeeNos)
            ->all();

        $this->merge([
            'employee_nos' => $employeeNos,
            'n_taxation_settings' => array_merge(
                (array) $this->input('n_taxation_settings', []),
                [
                    'bonuses' => $bonuses,
                    'employee_portions' => $employeePortions,
                ],
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

            foreach ((array) $this->input('n_taxation_settings.employee_portions', []) as $employeeNo => $employeePortion) {
                $employeeTotal = (float) ($employeePortion['salary'] ?? 0)
                    + (float) ($employeePortion['hazard_pay'] ?? 0)
                    + (float) ($employeePortion['longevity'] ?? 0);

                if (abs($employeeTotal - 100.0) > 0.0001) {
                    $validator->errors()->add(
                        "n_taxation_settings.employee_portions.{$employeeNo}",
                        'Each employee portion must total exactly 100.'
                    );
                }
            }
        });
    }
}
