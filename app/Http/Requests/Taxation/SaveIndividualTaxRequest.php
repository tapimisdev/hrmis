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
            'n_taxation_settings.tax_override' => ['nullable', 'array'],
            'n_taxation_settings.tax_override.employee_no' => ['required_with:n_taxation_settings.tax_override', 'exists:employee_information,employee_no'],
            'n_taxation_settings.tax_override.tax_type' => ['required_with:n_taxation_settings.tax_override', 'string', 'in:Salary Tax,Hazard Pay Tax,Longevity Tax'],
            'n_taxation_settings.tax_override.month_number' => ['required_with:n_taxation_settings.tax_override', 'integer', 'between:1,12'],
            'n_taxation_settings.tax_override.amount' => ['nullable', 'numeric', 'min:0'],
            'n_taxation_settings.tax_override.action' => ['nullable', 'string', 'in:upsert,delete'],
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
        $taxOverride = (array) $this->input('n_taxation_settings.tax_override', []);
        $normalizedEmployeeNo = trim((string) ($taxOverride['employee_no'] ?? ''));

        $this->merge([
            'employee_nos' => $employeeNos,
            'n_taxation_settings' => array_merge(
                (array) $this->input('n_taxation_settings', []),
                [
                    'bonuses' => $bonuses,
                    'employee_portions' => $employeePortions,
                    'tax_override' => $normalizedEmployeeNo !== '' ? [
                        'employee_no' => $normalizedEmployeeNo,
                        'tax_type' => trim((string) ($taxOverride['tax_type'] ?? '')),
                        'month_number' => (int) ($taxOverride['month_number'] ?? 0),
                        'amount' => array_key_exists('amount', $taxOverride) && $taxOverride['amount'] !== null && $taxOverride['amount'] !== ''
                            ? (float) $taxOverride['amount']
                            : null,
                        'action' => trim((string) ($taxOverride['action'] ?? 'upsert')) ?: 'upsert',
                    ] : null,
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

            $taxOverride = (array) $this->input('n_taxation_settings.tax_override', []);

            if ($taxOverride !== []) {
                $overrideEmployeeNo = (string) ($taxOverride['employee_no'] ?? '');
                $overrideAction = (string) ($taxOverride['action'] ?? 'upsert');

                if ($overrideEmployeeNo !== '' && !in_array($overrideEmployeeNo, (array) $this->input('employee_nos', []), true)) {
                    $validator->errors()->add(
                        'n_taxation_settings.tax_override.employee_no',
                        'Tax override employee must be included in the selected employees.'
                    );
                }

                if ($overrideAction !== 'delete' && !array_key_exists('amount', $taxOverride)) {
                    $validator->errors()->add(
                        'n_taxation_settings.tax_override.amount',
                        'The n taxation settings.tax override.amount field is required unless n taxation settings.tax override.action is in delete.'
                    );
                }

                if ($overrideAction !== 'delete' && (($taxOverride['amount'] ?? null) === null || $taxOverride['amount'] === '')) {
                    $validator->errors()->add(
                        'n_taxation_settings.tax_override.amount',
                        'The n taxation settings.tax override.amount field is required unless n taxation settings.tax override.action is in delete.'
                    );
                }
            }
        });
    }
}
