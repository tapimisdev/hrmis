<?php

namespace App\Services\Taxation;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RunForecastService
{
    protected $status = 'active';
    protected $employment_type = EmploymentTypesEnum::REGULAR->value;

    public function getAllEmployees()
    {
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', function ($join) {
                $join->on('ei.employee_no', '=', 'eo.employee_no')
                    ->whereRaw('eo.effectivity_date = (
                    SELECT MAX(eo2.effectivity_date)
                    FROM employee_organization eo2
                    WHERE eo2.employee_no = eo.employee_no
                )');
            })
            ->where('eo.employment_type_id', $this->employment_type)
            ->where('ei.account_status', $this->status)
            ->pluck('ei.employee_no');

        if ($employees->isEmpty()) {
            throw new HttpException(404, 'No employees found.');
        }

        return $employees;
    }

    public function createTaxation(array $payload): int
    {
        $taxationId = DB::table('taxations')->insertGetId([
            'year'                  => data_get($payload, 'year'),
            'hazard_tax_id'         => data_get($payload, 'hazardTaxId'),
            'salary_tax_id'         => data_get($payload, 'salaryTaxId'),
            'longevity_id'          => data_get($payload, 'longevityTaxId'),
            'train_law_id'          => data_get($payload, 'trainLawId'),

            // assumptions
            'mid_year'              => (bool) data_get($payload, 'assumptions.midYear', false),
            'year_end'              => (bool) data_get($payload, 'assumptions.yearEnd', false),
            'longevity'             => (bool) data_get($payload, 'assumptions.longevity', false),
            'hazard_pay'            => (bool) data_get($payload, 'assumptions.hazardPay', false),
            'less_bir_rr3_2015'     => (bool) data_get($payload, 'assumptions.lessBirRR32015', false),

            // allowable deductions
            'allowable_gsis'        => (int) data_get($payload, 'deductions.gsis', 0),
            'allowable_philhealth'  => (int) data_get($payload, 'deductions.philhealth', 0),
            'allowable_pagibig'     => (int) data_get($payload, 'deductions.pagibig', 0),

            // allocation
            'portion_hazard_pay'    => (int) data_get($payload, 'allocation.hazardPayPct', 0),
            'portion_basic_pay'     => (int) data_get($payload, 'allocation.basicPayPct', 0),
            'portion_longevity_pay' => (int) data_get($payload, 'allocation.longevityPct', 0),

            'is_active'             => true,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Others Earnings (batch insert)
        $othersEarnings = collect(data_get($payload, 'othersEarnings', []))
            ->filter(fn($r) => filled(data_get($r, 'name')))
            ->map(fn($r) => [
                'taxation_id' => $taxationId,
                'name'        => trim($r['name']),
                'tax_type'    => trim($r['tax_type'] ?? ''),
                'amount'      => (int) ($r['amount'] ?? 0),
                'created_at'  => now(),
                'updated_at'  => now(),
            ])
            ->all();

        if (!empty($othersEarnings)) {
            DB::table('taxation_other_earnings')->insert($othersEarnings);
        }

        // Others Deductions (batch insert)
        $othersDeductions = collect(data_get($payload, 'othersDeductions', []))
            ->filter(fn($r) => filled(data_get($r, 'name')))
            ->map(fn($r) => [
                'taxation_id' => $taxationId,
                'name'        => trim($r['name']),
                'amount'      => (int) ($r['amount'] ?? 0),
                'created_at'  => now(),
                'updated_at'  => now(),
            ])
            ->all();

        if (!empty($othersDeductions)) {
            DB::table('taxation_other_deductions')->insert($othersDeductions);
        }

        return $taxationId;
    }
}
