<?php

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // =========================
            // Assumptions (TAB B)
            // =========================
            'assumptions'                   => ['required', 'array'],
            'assumptions.basicPay'          => ['required', 'boolean'],
            'assumptions.midYear'           => ['required', 'boolean'],
            'assumptions.yearEnd'           => ['required', 'boolean'],
            'assumptions.longevity'         => ['required', 'boolean'],
            'assumptions.hazardPay'         => ['required', 'boolean'],
            'assumptions.lessBirRR32015'    => ['required', 'boolean'],

            // =========================
            // Earnings
            // =========================
            'othersEarnings'                => ['nullable', 'array'],
            'othersEarnings.*.id' => ['nullable', 'exists:taxation_employee_other_earnings,id'],
            'othersEarnings.*.name'         => ['required_with:earnings.others', 'string'],
            'othersEarnings.*.tax_type'     => [
                'required_with:earnings.others',
                'string',
                Rule::in(['taxable', 'non_taxable']),
            ],
            'othersEarnings.*.amount'       => ['required_with:earnings.others', 'numeric', 'min:1'],

            // =========================
            // Deductions
            // =========================
            'deductions'                    => ['required', 'array'],
            'deductions.gsis'               => ['required', 'boolean'],
            'deductions.philhealth'         => ['required', 'boolean'],
            'deductions.pagibig'            => ['required', 'boolean'],

            'othersDeductions'              => ['nullable', 'array'],
            'othersDeductions.*.id' => [
                'nullable',
                'exists:taxation_employee_other_deductions,id'
            ],
            'othersDeductions.*.name'       => ['required_with:deductions.others', 'string'],
            'othersDeductions.*.amount'     => ['required_with:deductions.others', 'numeric', 'min:1'],

            // =========================
            // Allocation (TAB C)
            // =========================
            'allocation'                   => ['required', 'array'],
            'allocation.hazardPayPct'      => ['required', 'numeric', 'min:0', 'max:100'],
            'allocation.basicPayPct'       => ['required', 'numeric', 'min:0', 'max:100'],
            'allocation.longevityPct'      => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Additional validation after rules
     */
    public function after(): array
    {
        return [
            function ($validator) {

                $hazard = $this->input('allocation.hazardPayPct', 0);
                $basic = $this->input('allocation.basicPayPct', 0);
                $long = $this->input('allocation.longevityPct', 0);

                $total = $hazard + $basic + $long;

                if ($total != 100) {
                    $validator->errors()->add(
                        'allocation',
                        'Hazard Pay, Basic Pay, and Longevity must total exactly 100%.'
                    );
                }
            }
        ];
    }
}