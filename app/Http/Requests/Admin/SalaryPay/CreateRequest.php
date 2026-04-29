<?php

namespace App\Http\Requests\Admin\SalaryPay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\EmploymentTypesEnum;
use Carbon\Carbon;

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
            'group_id' => [
                'required',
                Rule::when(
                    request('group_id') === 'custom',
                    ['string', Rule::in(['custom'])],
                    ['integer', Rule::exists('payroll_groups', 'id')]
                ),
            ],
            'cutoff' => [
                Rule::requiredIf(fn () => (string) $this->input('employment_type_id') !== EmploymentTypesEnum::REGULAR->value),
                'nullable',
                'string',
                Rule::in(['first_cutoff', 'second_cutoff']),
            ],
            'date' => 'required|date|before_or_equal:today',
            'apply_deduction' => [
                Rule::requiredIf(fn () => (string) $this->input('employment_type_id') === EmploymentTypesEnum::COS->value),
                'nullable',
                'string',
                Rule::in(['yes', 'no']),
            ],
            'deduction_deferred_cutoff' => [
                Rule::requiredIf(fn () => $this->isScheduledDeferredCosDeduction()),
                'nullable',
                'string',
                Rule::in(['first_cutoff', 'second_cutoff']),
            ],
            'deduction_deferred_date' => [
                Rule::requiredIf(fn () => $this->isScheduledDeferredCosDeduction()),
                'nullable',
                'date',
            ],
            'deduction_apply_options' => [
                Rule::excludeIf(fn () => !$this->isApplyingCosDeduction()),
                Rule::requiredIf(fn () => $this->isApplyingCosDeduction()),
                'array',
                'min:1',
            ],
            'deduction_apply_options.*' => ['string'],
            'deduction_defer_options' => [
                Rule::excludeIf(fn () => !$this->isDeferredCosDeduction()),
                Rule::requiredIf(fn () => $this->isDeferredCosDeduction()),
                'array',
                'min:1',
            ],
            'deduction_defer_options.*' => ['string', Rule::in(['tbd', 'next_cutoff'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->isDeferredCosDeduction() || !$this->input('date') || !$this->input('cutoff')) {
            return;
        }

        if ($this->deferOptions()->contains('next_cutoff')) {
            $this->merge($this->expectedDeferredDeduction());
            return;
        }

        $this->merge([
            'deduction_deferred_cutoff' => null,
            'deduction_deferred_date' => null,
        ]);
    }

    private function isDeferredCosDeduction(): bool
    {
        return (string) $this->input('employment_type_id') === EmploymentTypesEnum::COS->value
            && $this->input('apply_deduction', 'yes') === 'no';
    }

    private function isApplyingCosDeduction(): bool
    {
        return (string) $this->input('employment_type_id') === EmploymentTypesEnum::COS->value
            && $this->input('apply_deduction', 'yes') === 'yes';
    }

    private function isScheduledDeferredCosDeduction(): bool
    {
        return $this->isDeferredCosDeduction()
            && $this->deferOptions()->contains('next_cutoff');
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isApplyingCosDeduction()) {
                $options = $this->applyOptions();

                if (!$options->contains('current') && !$options->contains(fn ($option) => str_starts_with($option, 'payroll:'))) {
                    $validator->errors()->add('deduction_apply_options', 'Select current deductions or at least one pending payroll deduction.');
                }
            }

            if ($this->isDeferredCosDeduction()) {
                $deferOptions = $this->deferOptions();

                if ($deferOptions->contains('tbd') && $deferOptions->contains('next_cutoff')) {
                    $validator->errors()->add('deduction_defer_options', 'Choose only one deduction schedule option.');
                }
            }

            if (!$this->isDeferredCosDeduction() || !$this->input('date') || !$this->input('cutoff') || !$this->deferOptions()->contains('next_cutoff')) {
                return;
            }

            $expected = $this->expectedDeferredDeduction();

            if ($this->input('deduction_deferred_cutoff') !== $expected['deduction_deferred_cutoff']) {
                $validator->errors()->add('deduction_deferred_cutoff', 'The deduction schedule must be the next payroll cutoff.');
            }

            if ($this->input('deduction_deferred_date') !== $expected['deduction_deferred_date']) {
                $validator->errors()->add('deduction_deferred_date', 'The deduction schedule date must match the next payroll cutoff.');
            }
        });
    }

    private function applyOptions()
    {
        return collect($this->input('deduction_apply_options', []));
    }

    private function deferOptions()
    {
        return collect($this->input('deduction_defer_options', []));
    }

    private function expectedDeferredDeduction(): array
    {
        $date = Carbon::parse($this->input('date'));

        if ($this->input('cutoff') === 'second_cutoff') {
            return [
                'deduction_deferred_cutoff' => 'first_cutoff',
                'deduction_deferred_date' => $date->copy()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            ];
        }

        return [
            'deduction_deferred_cutoff' => 'second_cutoff',
            'deduction_deferred_date' => $date->copy()->day(16)->format('Y-m-d'),
        ];
    }
}
