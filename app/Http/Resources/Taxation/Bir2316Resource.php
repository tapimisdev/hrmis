<?php

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Bir2316Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = (array) ($this->snapshot_data ?? []);

        return [
            'id' => (int) $this->id,
            'employee_id' => (int) $this->employee_id,
            'annual_tax_computation_id' => $this->annual_tax_computation_id ? (int) $this->annual_tax_computation_id : null,
            'taxable_year' => (int) $this->taxable_year,
            'status' => (string) $this->status,
            'generated_at' => optional($this->generated_at)->toDateTimeString(),
            'locked_at' => optional($this->locked_at)->toDateTimeString(),
            'employee' => [
                'no' => (string) $this->employee_no,
                'name' => (string) $this->employee_name,
                'tin' => $this->employee_tin,
                'address' => $this->employee_address,
                'position' => $this->position,
                'employment_type' => $this->employment_type,
            ],
            'employer' => [
                'name' => $this->employer_name,
                'tin' => $this->employer_tin,
                'address' => $this->employer_address,
                'rdo_code' => $this->rdo_code,
            ],
            'compensation' => [
                'annual_basic_salary' => (float) $this->annual_basic_salary,
                'hazard_pay' => (float) $this->hazard_pay,
                'longevity_pay' => (float) $this->longevity_pay,
                'government_bonuses' => (float) $this->government_bonuses,
                'de_minimis' => (float) $this->de_minimis,
                'gross_compensation_income' => (float) $this->gross_compensation_income,
                'tax_exempt_bonus' => (float) $this->tax_exempt_bonus,
                'net_taxable_benefit' => (float) $this->net_taxable_benefit,
                'gross_taxable_income' => (float) $this->gross_taxable_income,
                'allowable_deductions' => (float) $this->allowable_deductions,
                'net_taxable_income' => (float) $this->net_taxable_income,
                'annual_tax_due' => (float) $this->annual_tax_due,
                'tax_withheld' => (float) $this->tax_withheld,
                'tax_refund_or_payable' => (float) $this->tax_refund_or_payable,
            ],
            'snapshot_data' => $snapshot,
            'certification' => (array) data_get($snapshot, 'certification', []),
        ];
    }
}
