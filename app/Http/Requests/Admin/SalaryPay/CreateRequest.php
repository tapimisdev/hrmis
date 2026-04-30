<?php

namespace App\Http\Requests\Admin\SalaryPay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\EmploymentTypesEnum;

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
            'deduction_defer_options.*' => ['string', Rule::in(['tbd'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->isDeferredCosDeduction()) {
            return;
        }

        $this->merge([
            'deduction_defer_options' => $this->deferOptions()->isNotEmpty()
                ? $this->deferOptions()->values()->all()
                : ['tbd'],
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
        return false;
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
}
