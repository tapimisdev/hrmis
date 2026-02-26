<?php

namespace App\Services\Taxation;

use App\Enums\TableSettingsEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ForecastComputationService
{
    public function createTaxationEmployees(
        array $payload,
        int $taxationId,
        string $employeeNo,
        array $computedAnnualTaxableAmounts
    ): int {
        $taxationEmployeeId = DB::table('taxation_employees')->insertGetId([
            'taxation_id'           => $taxationId,
            'year'                  => data_get($payload, 'year'),
            'employee_no'           => $employeeNo,

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

            'amount_gross'          => (float) data_get($payload, 'amounts.gross', 0),
            'amount_annual_total_allowables' => (float) data_get($payload, 'amounts.annualTotalAllowables', 0),

            'amount_annual_taxable' => (float) data_get($payload, 'amounts.annualTaxable', 0),
            'amount_annual_tax'     => (float) data_get($payload, 'amounts.annualTax', 0),
            'amount_monthly_tax'    => (float) data_get($payload, 'amounts.monthlyTax', 0),


            'amount_mid_year_bonus' => (float) data_get($payload, 'amounts.midYearBonus', 0),
            'amount_year_end_bonus' => (float) data_get($payload, 'amounts.yearEndBonus', 0),
            'amount_longevity_pay'  => (float) data_get($payload, 'amounts.longevityPay', 0),
            'amount_hazard_pay'     => (float) data_get($payload, 'amounts.hazardPay', 0),

            'amount_other_earnings_taxable'     => (float) data_get($payload, 'amounts.otherEarningsTaxable', 0),
            'amount_other_earnings_non_taxable' => (float) data_get($payload, 'amounts.otherEarningsNonTaxable', 0),
            
            'amount_other_deductions' => (float) data_get($payload, 'amounts.otherDeductions', 0),

            'amount_portion_hazard_pay' => (float) data_get($payload, 'amounts.portionHazardPay', 0),
            'amount_portion_basic_pay' => (float) data_get($payload, 'amounts.portionBasicPay', 0),
            'amount_portion_longevity_pay' => (float) data_get($payload, 'amounts.portionLongevityPay', 0),

            'remarks'          => json_encode($payload['remarks'] ?? []),
            'raw_payload'          => json_encode($payload),
            'is_active'             => true,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Employee Others Earnings
        $earnings = collect(data_get($payload, 'othersEarnings', []))
            ->filter(fn($r) => filled(data_get($r, 'name')))
            ->map(fn($r) => [
                'taxation_employee_id' => $taxationEmployeeId,
                'name'                 => trim($r['name']),
                'tax_type'             => trim($r['tax_type'] ?? ''),
                'amount'               => (int) ($r['amount'] ?? 0),
                'created_at'           => now(),
                'updated_at'           => now(),
            ])
            ->all();

        if (!empty($earnings)) {
            DB::table('taxation_employee_other_earnings')->insert($earnings);
        }

        // Employee Others Deductions
        $deductions = collect(data_get($payload, 'othersDeductions', []))
            ->filter(fn($r) => filled(data_get($r, 'name')))
            ->map(fn($r) => [
                'taxation_employee_id' => $taxationEmployeeId,
                'name'                 => trim($r['name']),
                'amount'               => (int) ($r['amount'] ?? 0),
                'created_at'           => now(),
                'updated_at'           => now(),
            ])
            ->all();

        if (!empty($deductions)) {
            DB::table('taxation_employee_other_deductions')->insert($deductions);
        }

        if(!empty($payload['midyear'])) {
            if (data_get($payload, 'assumptions.midYear') === true) {
                DB::table('taxation_employee_bonus')->insert([
                    'taxation_employee_id' => $taxationEmployeeId,
                    'employee_no'          => $employeeNo,
                    'type'                 => 'midyear',

                    'as_of'                => data_get($payload, 'midyear.as_of'),
                    'basic_salary_as_of'   => (float) data_get($payload, 'midyear.basic_salary_as_of', 0),
                    'salary_effective_date' => data_get($payload, 'midyear.salary_effective_date'),
                    'eligible'             => (bool) data_get($payload, 'midyear.eligible', false),
                    'months_of_service'    => (int) data_get($payload, 'midyear.months_of_service', 0),
                    'service_start'        => data_get($payload, 'midyear.service_start'),
                    'service_end'          => data_get($payload, 'midyear.service_end'),

                    'amount'               => (float) data_get($payload, 'midyear.amount', 0),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }

        if(!empty($payload['year_end'])) {
            if (data_get($payload, 'assumptions.yearEnd') === true) {
                DB::table('taxation_employee_bonus')->insert([
                    'taxation_employee_id' => $taxationEmployeeId,
                    'employee_no'          => $employeeNo,
                    'type'                 => 'year_end',

                    'as_of'                => data_get($payload, 'year_end.as_of'),
                    'basic_salary_as_of'   => (float) data_get($payload, 'year_end.basic_salary_as_of', 0),
                    'salary_effective_date' => data_get($payload, 'year_end.salary_effective_date'),
                    'eligible'             => (bool) data_get($payload, 'year_end.eligible', false),
                    'months_of_service'    => (int) data_get($payload, 'year_end.months_of_service', 0),
                    'service_start'        => data_get($payload, 'year_end.service_start'),
                    'service_end'          => data_get($payload, 'year_end.service_end'),

                    'amount'               => (float) data_get($payload, 'year_end.amount', 0),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }

        DB::table('tax_computation_logs')->insert([
            'taxation_employee_id' => $taxationEmployeeId,
            'employee_no'          => $employeeNo,
            
            'annual_income'        => (float) data_get($computedAnnualTaxableAmounts, 'annual_income', 0), 
            'fixed_tax'            => (float) data_get($computedAnnualTaxableAmounts, 'fixed_tax', 0),
            'tax_rate'             => (float) data_get($computedAnnualTaxableAmounts, 'tax_rate', 0),
            'excess_over'          => (float) data_get($computedAnnualTaxableAmounts, 'excess_over', 0),
            'excess_amount'        => (float) data_get($computedAnnualTaxableAmounts, 'excess_amount', 0),
            'tax'                  => (float) data_get($computedAnnualTaxableAmounts, 'tax', 0),
            'monthly_tax'          => (float) data_get($computedAnnualTaxableAmounts, 'monthly_tax', 0),
            'bracket_from'         => (float) data_get($computedAnnualTaxableAmounts, 'bracket.bracket.from', 0),
            'bracket_to'           => (float) data_get($computedAnnualTaxableAmounts, 'bracket.bracket.to', 0),

            'remarks'              => data_get($computedAnnualTaxableAmounts, 'remarks', ''),

            'raw_payload'          => json_encode($computedAnnualTaxableAmounts),

            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return $taxationEmployeeId;
    }

    public function annualSalaryTotalByMonth(
    string $employeeNo,
    int $year = 2026,
    ): array {
        // ----------------------------
        // 1) Response skeleton (SAME FORMAT ALWAYS)
        // ----------------------------
        $makeResponse = function () use ($employeeNo, $year) {
            return [
                'employee_no'           => $employeeNo,
                'year'                  => $year,
                'period_start'          => null,
                'period_end'            => null,

                // latest salary (as-of period_end)
                'monthly_salary'        => 0.0,
                'salary_effective_date' => null,

                'annual_total'          => 0.0,
                'months_covered'        => 0,
                'hazard_pay'            => 0.0,

                'midyear'               => [
                    'as_of'                 => null,
                    'basic_salary_as_of'    => 0.0,
                    'salary_effective_date' => null,

                    'eligible'              => false,
                    'months_of_service'     => 0,
                    'service_start'         => null,
                    'service_end'           => null,

                    'amount'                => 0.0,
                    'reason'                => null,
                ],

                'year_end'              => [
                    'as_of'                 => null,
                    'basic_salary_as_of'    => 0.0,
                    'salary_effective_date' => null,

                    'eligible'              => false,
                    'months_of_service'     => 0,
                    'service_start'         => null,
                    'service_end'           => null,

                    'amount'                => 0.0,
                    'reason'                => null,
                ],

                'remarks'               => '',
            ];
        };

        $res = $makeResponse();

        // ----------------------------
        // 2) Setup year window
        // ----------------------------
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd   = Carbon::create($year, 12, 31)->endOfDay();

        $midyearAsOf = Carbon::create($year, 5, 15)->endOfDay();
        $yearEndAsOf = Carbon::create($year, 10, 31)->endOfDay();

        $res['midyear']['as_of']  = $midyearAsOf->toDateString();
        $res['year_end']['as_of'] = $yearEndAsOf->toDateString();

        // Employee employment window (from DB)
        $empDates = $this->getEmployeeStartAndEndDate($employeeNo);
        $rawStart = $empDates['start_date'] ?? null;
        $rawEnd   = $empDates['end_date'] ?? null;

        if (!$rawStart) {
            $res['remarks'] = 'No date hired company.';
            $res['midyear']['reason'] = 'No date hired company.';
            $res['year_end']['reason'] = 'No date hired company.';
            return $res;
        }

        // ----------------------------
        // 3) Parse + clamp employment window to year
        // ----------------------------
        $start = Carbon::parse($rawStart)->startOfDay();
        $end   = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : $yearEnd->copy();

        if ($start->lt($yearStart)) $start = $yearStart->copy();
        if ($end->gt($yearEnd))     $end   = $yearEnd->copy();

        $res['period_start'] = $start->toDateString();
        $res['period_end']   = $end->toDateString();

        if ($end->lt($start)) {
            $res['remarks'] = 'No computable period.';
            $res['midyear']['reason'] = 'No computable period.';
            $res['year_end']['reason'] = 'No computable period.';
            return $res;
        }

        // ----------------------------
        // 4) Months covered (simple)
        // ----------------------------
        $monthsCovered = (($end->year - $start->year) * 12)
            + ($end->month - $start->month)
            + 1;

        $res['months_covered'] = (int) max(0, $monthsCovered);

        // ----------------------------
        // 5) Latest salary as-of period_end
        // ----------------------------
        $latestSalaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $end->toDateString())
            ->orderBy('effectivity_date', 'desc')
            ->first();

        $latestMonthlySalary = (float) str_replace(',', '', (string) ($latestSalaryRow->amount ?? 0));

        $res['monthly_salary']        = (float) round($latestMonthlySalary, 2);
        $res['salary_effective_date'] = $latestSalaryRow->effectivity_date ?? null;

        // Annual total now uses latest salary * months covered
        $annualTotal = $latestMonthlySalary * $res['months_covered'];
        $res['annual_total'] = (float) round($annualTotal, 2);

        // Hazard pay uses latest monthly salary (15%)
        $res['hazard_pay'] = (float) round($latestMonthlySalary * 0.15, 2);

        // ----------------------------
        // 6) MIDYEAR
        // ----------------------------
        $midyearSalaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $midyearAsOf->toDateString())
            ->orderBy('effectivity_date', 'desc')
            ->first();

        $midyearBasicSalary = (float) str_replace(',', '', (string) ($midyearSalaryRow->amount ?? 0));

        $midyearEligibility = $this->isEligibleForMidYear($employeeNo, $year);

        $midyearComputation = $this->computeMidYearBonusAmount(
            $midyearBasicSalary,
            (int) ($midyearEligibility['months_of_service'] ?? 0)
        );

        if (!($midyearEligibility['eligible'] ?? false)) {
            $midyearComputation['eligible'] = false;
            $midyearComputation['amount']   = 0;
            $midyearComputation['reason']   = $midyearEligibility['reason'] ?? 'Not eligible.';
        }

        $res['midyear'] = [
            'as_of'                 => $midyearAsOf->toDateString(),
            'basic_salary_as_of'    => (float) round($midyearBasicSalary, 2),
            'salary_effective_date' => $midyearSalaryRow->effectivity_date ?? null,

            'eligible'              => (bool) ($midyearEligibility['eligible'] ?? false),
            'months_of_service'     => (int) ($midyearEligibility['months_of_service'] ?? 0),
            'service_start'         => $midyearEligibility['service_start'] ?? null,
            'service_end'           => $midyearEligibility['service_end'] ?? null,

            'amount'                => (float) round(($midyearComputation['amount'] ?? 0), 2),
            'reason'                => $midyearComputation['reason'] ?? null,
        ];

        // ----------------------------
        // 7) YEAR-END
        // ----------------------------
        $yearEndSalaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $yearEndAsOf->toDateString())
            ->orderBy('effectivity_date', 'desc')
            ->first();

        $yearEndBasicSalary = (float) str_replace(',', '', (string) ($yearEndSalaryRow->amount ?? 0));

        $yearEndEligibility = $this->isEligibleForYearEndForecast($employeeNo, $year);

        $yearEndComputation = $this->computeYearEndBonusAmount(
            $yearEndBasicSalary,
            (int) ($yearEndEligibility['months_of_service'] ?? 0)
        );

        if (!($yearEndEligibility['eligible'] ?? false)) {
            $yearEndComputation['eligible'] = false;
            $yearEndComputation['amount']   = 0;
            $yearEndComputation['reason']   = $yearEndEligibility['reason'] ?? 'Not eligible.';
        }

        $res['year_end'] = [
            'as_of'                 => $yearEndAsOf->toDateString(),
            'basic_salary_as_of'    => (float) round($yearEndBasicSalary, 2),
            'salary_effective_date' => $yearEndSalaryRow->effectivity_date ?? null,

            'eligible'              => (bool) ($yearEndEligibility['eligible'] ?? false),
            'months_of_service'     => (int) ($yearEndEligibility['months_of_service'] ?? 0),
            'service_start'         => $yearEndEligibility['service_start'] ?? null,
            'service_end'           => $yearEndEligibility['service_end'] ?? null,

            'amount'                => (float) round(($yearEndComputation['amount'] ?? 0), 2),
            'reason'                => $yearEndComputation['reason'] ?? null,
        ];

        return $res;
    }

    private function isEligibleForMidYear(
        string $employeeNo,
        int $year = 2026,
        ?string $performanceRating = null, // optional: pass if you have it
    ): array {
        // Midyear "as of" date
        $asOf = Carbon::create($year, 5, 15)->endOfDay();

        // Service window for Midyear eligibility:
        // July 1 (previous year) to May 15 (current year)
        $serviceStartWindow = Carbon::create($year - 1, 7, 1)->startOfDay();
        $serviceEndWindow   = $asOf->copy();

        // Employee employment window (from DB)
        $empDates = $this->getEmployeeStartAndEndDate($employeeNo);
        $rawStart = $empDates['start_date'] ?? null;
        $rawEnd   = $empDates['end_date'] ?? null;

        if (!$rawStart) {
            return [
                'eligible' => false,
                'reason'   => 'No start_date found.',
                'months_of_service' => 0,
                'service_start' => null,
                'service_end'   => null,
                'as_of'         => $asOf->toDateString(),
            ];
        }

        $start = Carbon::parse($rawStart)->startOfDay();
        $end   = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : null;

        // Must be in service as of May 15
        // Eligible if end is null OR end >= asOf
        if ($end && $end->lt($asOf)) {
            return [
                'eligible' => false,
                'reason'   => 'Not in service as of May 15.',
                'months_of_service' => 0,
                'service_start' => null,
                'service_end'   => null,
                'as_of'         => $asOf->toDateString(),
            ];
        }

        // Effective service window intersection:
        // max(start, Jul 1 prev year)  to  min(end(or asOf), asOf)
        $effectiveStart = $start->gt($serviceStartWindow) ? $start->copy() : $serviceStartWindow->copy();
        $effectiveEnd   = ($end ? ($end->lt($serviceEndWindow) ? $end->copy() : $serviceEndWindow->copy()) : $serviceEndWindow->copy());

        if ($effectiveEnd->lt($effectiveStart)) {
            return [
                'eligible' => false,
                'reason'   => 'No service within the eligibility window.',
                'months_of_service' => 0,
                'service_start' => $effectiveStart->toDateString(),
                'service_end'   => $effectiveEnd->toDateString(),
                'as_of'         => $asOf->toDateString(),
            ];
        }

        // Month counting rule (HRIS-friendly):
        // Any day in a month counts as 1 month => count inclusive months
        $monthsOfService = (($effectiveEnd->year - $effectiveStart->year) * 12)
            + ($effectiveEnd->month - $effectiveStart->month)
            + 1;

        if ($monthsOfService < 4) {
            return [
                'eligible' => false,
                'reason'   => 'Less than 4 months of service within the eligibility window.',
                'months_of_service' => $monthsOfService,
                'service_start' => $effectiveStart->toDateString(),
                'service_end'   => $effectiveEnd->toDateString(),
                'as_of'         => $asOf->toDateString(),
            ];
        }

        // Optional performance check (only if you pass it)
        if ($performanceRating !== null) {
            // Keep this simple: treat "Satisfactory" or higher as eligible.
            // If you use numeric/enum rankings, swap this block with your own comparator.
            $allowed = ['Satisfactory', 'Very Satisfactory', 'Outstanding'];
            if (!in_array($performanceRating, $allowed, true)) {
                return [
                    'eligible' => false,
                    'reason'   => 'Performance rating below minimum.',
                    'months_of_service' => $monthsOfService,
                    'service_start' => $effectiveStart->toDateString(),
                    'service_end'   => $effectiveEnd->toDateString(),
                    'as_of'         => $asOf->toDateString(),
                ];
            }
        }

        return [
            'eligible' => true,
            'reason'   => 'Eligible for Midyear Bonus.',
            'months_of_service' => $monthsOfService,
            'service_start' => $effectiveStart->toDateString(),
            'service_end'   => $effectiveEnd->toDateString(),
            'as_of'         => $asOf->toDateString(),
        ];
    }

    private function isEligibleForYearEndForecast(
        string $employeeNo,
        int $year = 2026,
        ?string $performanceRating = null
    ): array {
        $asOf = Carbon::create($year, 12, 31)->endOfDay();

        $serviceStartWindow = Carbon::create($year, 1, 1)->startOfDay();
        $serviceEndWindow   = $asOf->copy();

        $empDates = $this->getEmployeeStartAndEndDate($employeeNo);
        $rawStart = $empDates['start_date'] ?? null;
        $rawEnd   = $empDates['end_date'] ?? null;

        if (!$rawStart) {
            return [
                'eligible' => false,
                'reason'   => 'No start_date found.',
                'months_of_service' => 0,
                'service_start' => null,
                'service_end'   => null,
                'as_of'         => $asOf->toDateString(),
            ];
        }

        $start = Carbon::parse($rawStart)->startOfDay();
        $end   = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : null;

        // Forecast rule: only disqualify if there is a KNOWN end_date before Oct 31
        if ($end && $end->lt($asOf)) {
            return [
                'eligible' => false,
                'reason'   => 'Known end_date is before October 31 (not in service as of cut-off).',
                'months_of_service' => 0,
                'service_start' => null,
                'service_end'   => null,
                'as_of'         => $asOf->toDateString(),
            ];
        }

        $effectiveStart = $start->gt($serviceStartWindow) ? $start->copy() : $serviceStartWindow->copy();
        $effectiveEnd   = ($end
            ? ($end->lt($serviceEndWindow) ? $end->copy() : $serviceEndWindow->copy())
            : $serviceEndWindow->copy()
        );

        if ($effectiveEnd->lt($effectiveStart)) {
            return [
                'eligible' => false,
                'reason'   => 'No service within the eligibility window.',
                'months_of_service' => 0,
                'service_start' => $effectiveStart->toDateString(),
                'service_end'   => $effectiveEnd->toDateString(),
                'as_of'         => $asOf->toDateString(),
            ];
        }

        $monthsOfService = (($effectiveEnd->year - $effectiveStart->year) * 12)
            + ($effectiveEnd->month - $effectiveStart->month)
            + 1;

        // dd($monthsOfService, $effectiveStart->toDateString(), $effectiveEnd->toDateString(), $asOf->toDateString());
        if ($monthsOfService < 4) {
            return [
                'eligible' => false,
                'reason'   => 'Less than 4 months of service within Jan 1–Oct 31 window.',
                'months_of_service' => $monthsOfService,
                'service_start' => $effectiveStart->toDateString(),
                'service_end'   => $effectiveEnd->toDateString(),
                'as_of'         => $asOf->toDateString(),
            ];
        }

        if ($performanceRating !== null) {
            $allowed = ['Satisfactory', 'Very Satisfactory', 'Outstanding'];
            if (!in_array($performanceRating, $allowed, true)) {
                return [
                    'eligible' => false,
                    'reason'   => 'Performance rating below minimum.',
                    'months_of_service' => $monthsOfService,
                    'service_start' => $effectiveStart->toDateString(),
                    'service_end'   => $effectiveEnd->toDateString(),
                    'as_of'         => $asOf->toDateString(),
                ];
            }
        }

        return [
            'eligible' => true,
            'reason'   => 'Forecast: Eligible for Year-End Bonus (subject to continued service until Oct 31).',
            'months_of_service' => $monthsOfService,
            'service_start' => $effectiveStart->toDateString(),
            'service_end'   => $effectiveEnd->toDateString(),
            'as_of'         => $asOf->toDateString(),
        ];
    }

    private function computeYearEndBonusAmount(float $basicSalaryAsOfOct31, int $monthsOfService): array
    {
        // Gov’t year-end bonus: up to 1 month basic salary, prorated if < 12 months (but must be >= 4 months eligible)
        if ($monthsOfService <= 0 || $basicSalaryAsOfOct31 <= 0) {
            return [
                'eligible' => false,
                'amount'   => 0,
                'factor'   => 0,
                'prorated' => false,
                'reason'   => 'No computable amount.',
            ];
        }

        $amount = round($basicSalaryAsOfOct31, 2);

        return [
            'eligible' => true,
            'amount'   => $amount,
            'factor'   => 0,
            'prorated' => false,
            'reason'   => $monthsOfService < 12 ? 'Prorated year-end bonus.' : 'Full year-end bonus.',
        ];
    }

    private function computeMidYearBonusAmount(
        float $monthlyBasicSalary,
        int $monthsOfService
    ): array {
        // Normalize inputs
        $monthsOfService = max(0, (int) $monthsOfService);
        $monthlyBasicSalary = (float) $monthlyBasicSalary;

        // Guard rails
        if ($monthlyBasicSalary <= 0) {
            return [
                'eligible' => false,
                'amount'   => 0.00,
                'months'   => $monthsOfService,
                'reason'   => 'Monthly basic pay is zero or invalid.',
                'prorated' => false,
                'factor'   => 0.00,
            ];
        }

        // Eligibility: at least 4 months aggregate service within the qualifying window
        if ($monthsOfService < 4) {
            return [
                'eligible' => false,
                'amount'   => 0.00,
                'months'   => $monthsOfService,
                'reason'   => 'Not eligible: less than 4 months aggregate service within the qualifying period (July 1 to May 15).',
                'prorated' => false,
                'factor'   => 0.00,
            ];
        }

        // Mid-Year Bonus: 1 month basic pay
        $amount = round($monthlyBasicSalary, 2);

        return [
            'eligible' => true,
            'amount'   => $amount,
            'months'   => $monthsOfService,
            'reason'   => 'Eligible: at least 4 months aggregate service within the qualifying period (July 1 to May 15).',
            'prorated' => false,
            'factor'   => 1.00,
        ];
    }

    public function ComputeLongevity(
        $employee_no,
        $year = 2026,
        $longevity_tax_id
    ): array {

        // Get longevity component for the given year
        $longevityComponentId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $longevity_tax_id)
            ->where('year', $year)
            ->value('id'); // returns scalar, not stdClass

        // Guard: component not found
        if (!$longevityComponentId) {
            Log::warning('Longevity component year not found', [
                'payroll_component_id' => $longevity_tax_id,
                'year' => $year,
                'employee_no' => $employee_no,
            ]);

            $longevityTotal = 0;
        } else {

            // Compute longevity total for employee
            $longevityTotal = DB::table('employee_payroll_components')
                ->where('tax_deduction_id', $longevityComponentId)
                ->where('employee_no', $employee_no)
                ->sum('amount'); // numeric value
        }

        // // Safe logging
        // Log::info('Longevity computation', [
        //     'employee_no'        => $employee_no,
        //     'year'               => $year,
        //     'longevity_total'    => $longevityTotal,
        //     'avg_monthly' => round(((double) $longevityTotal / 12), 2),
        // ]);

        return [
            'employee_no'        => $employee_no,
            'year'               => $year,
            'longevity_total'    => $longevityTotal,
            'avg_monthly' => round(((float) $longevityTotal / 12), 2),
        ];
    }

    public function getAllowablesDeductions($employee_no, $year): array
    {
        // IDs (string values) coming from your enum
        $gisId        = TableSettingsEnum::GSIS->value;
        $pagibigId    = TableSettingsEnum::PAGIBIG->value;
        $philhealthId = TableSettingsEnum::PHILHEALTH->value;

        $moduleIds = [$gisId, $pagibigId, $philhealthId];

        // Fetch all needed rows in one go
        $rows = DB::table('module_tab_employees')
            ->select('module_tab_id', 'month', 'amount')
            ->where('employee_no', $employee_no)
            ->where('year', $year)
            ->whereIn('module_tab_id', $moduleIds)
            ->get();

        // Group by module_tab_id
        $grouped = $rows->groupBy('module_tab_id');

        // GSIS total (normal sum)
        $gisTotal = (float) ($grouped->get($gisId, collect())->sum('amount') ?? 0.0);

        // PHILHEALTH total (normal sum)
        $philhealthTotal = (float) ($grouped->get($philhealthId, collect())->sum('amount') ?? 0.0);

        // PAGIBIG total (cap ₱200 per month)
        $pagibigRows = $grouped->get($pagibigId, collect());

        $pagibigTotal = (float) $pagibigRows
            ->groupBy('month')
            ->map(function ($items) {
                $monthlyTotal = (float) $items->sum('amount');   // sum all entries for that month
                return min($monthlyTotal, 200.00);               // cap to 200 per month
            })
            ->sum();

        // Return totals (2 decimals)
        return [
            'gsis'       => round($gisTotal, 2),
            'pagibig'    => round($pagibigTotal, 2),
            'philhealth' => round($philhealthTotal, 2),
        ];
    }

    public function computeAnnualTax(
        string $employee_no,
        float $annual_taxable,
        int $train_law_id,
        array $allocations
    ): array {

        // ----------------------------
        // 1) Response skeleton (SAME FORMAT ALWAYS)
        // ----------------------------
        $makeResponse = function () use ($employee_no) {
            return [
                'employee_no'   => $employee_no,
                'annual_income' => 0.00,

                'fixed_tax'     => 0.00,
                'tax_rate'      => 0.00,
                'excess_over'   => 0.00,
                'excess_amount' => 0.00,

                'tax'           => 0.00,
                'monthly_tax'   => 0.00,

                'allocation'    => [
                    'pct' => [
                        'hazard_pay'    => 0.00,
                        'basic_pay'     => 0.00,
                        'longevity_pay' => 0.00,
                        'total'         => 0.00,
                        'unallocated'   => 0.00,
                    ],
                    'annual' => [
                        'hazard_pay'    => 0.00,
                        'basic_pay'     => 0.00,
                        'longevity_pay' => 0.00,
                        'total'         => 0.00,
                        'unallocated'   => 0.00,
                    ],
                    'monthly' => [
                        'hazard_pay'    => 0.00,
                        'basic_pay'     => 0.00,
                        'longevity_pay' => 0.00,
                        'total'         => 0.00,
                        'unallocated'   => 0.00,
                    ],
                ],

                'bracket'       => [
                    'from' => null,
                    'to'   => null,
                ],

                'remarks'       => '',
            ];
        };

        // Helper: reconciles rounded components to match total exactly
        $reconcile = function (array $parts, float $expectedTotal, string $adjustKey = 'hazard_pay'): array {
            // parts keys: hazard_pay, basic_pay, longevity_pay
            $sum = round(array_sum($parts), 2);
            $diff = round($expectedTotal - $sum, 2);

            if ($diff != 0.00) {
                if (!array_key_exists($adjustKey, $parts)) {
                    $adjustKey = array_key_first($parts); // fallback
                }
                $parts[$adjustKey] = round($parts[$adjustKey] + $diff, 2);
            }

            // recompute total after adjustment
            $sumAfter = round(array_sum($parts), 2);

            return [
                'parts' => $parts,
                'total' => $sumAfter,
                'diff_applied' => $diff,
            ];
        };

        $res = $makeResponse();

        // ----------------------------
        // 2) Read allocations (percent)
        // ----------------------------
        $hazardPct    = round((float) ($allocations['hazardPayPct'] ?? 0), 2);
        $basicPct     = round((float) ($allocations['basicPayPct'] ?? 0), 2);
        $longevityPct = round((float) ($allocations['longevityPct'] ?? 0), 2);

        $totalPct = round($hazardPct + $basicPct + $longevityPct, 2);
        $unallocatedPct = round(max(0, 100 - $totalPct), 2);

        $res['allocation']['pct'] = [
            'hazard_pay'    => $hazardPct,
            'basic_pay'     => $basicPct,
            'longevity_pay' => $longevityPct,
            'total'         => $totalPct,
            'unallocated'   => $unallocatedPct,
        ];

        // ----------------------------
        // 3) Normalize annual taxable
        // ----------------------------
        $annual_taxable = round((float) $annual_taxable, 2);
        $res['annual_income'] = $annual_taxable;

        if ($annual_taxable <= 0) {
            $res['remarks'] = 'No taxable income.';
            return $res;
        }

        // ----------------------------
        // 4) Get TRAIN brackets
        // ----------------------------
        $brackets = $this->getTrainLaw($train_law_id);

        if ($brackets->isEmpty()) {
            $res['remarks'] = 'No TRAIN law table found.';
            return $res;
        }

        // ----------------------------
        // 5) Select bracket
        // ----------------------------
        $selectedBracket = null;

        foreach ($brackets as $item) {
            $incomeFrom = (float) $item->income_from;
            $incomeTo   = $item->income_to !== null ? (float) $item->income_to : null;

            if ($incomeTo === null) {
                if ($annual_taxable >= $incomeFrom) {
                    $selectedBracket = $item;
                    break;
                }
            } else {
                if ($annual_taxable >= $incomeFrom && $annual_taxable <= $incomeTo) {
                    $selectedBracket = $item;
                    break;
                }
            }
        }

        if (!$selectedBracket) {
            $res['remarks'] = 'No matching tax bracket.';
            return $res;
        }

        // ----------------------------
        // 6) Compute tax
        // ----------------------------
        $fixedTax   = round((float) $selectedBracket->fixed_tax, 2);
        $taxRate    = round((float) $selectedBracket->tax_rate, 2);
        $excessOver = round((float) $selectedBracket->excess_over, 2);

        $excess = round(max(0, $annual_taxable - $excessOver), 2);

        $computedTax = $fixedTax + ($excess * ($taxRate / 100));
        $computedTax = round($computedTax, 2);

        $monthlyTax = round($computedTax / 12, 2);

        // ----------------------------
        // 7) Allocation amounts with reconciliation
        // ----------------------------

        // Annual parts (rounded)
        $annualParts = [
            'hazard_pay'    => round($computedTax * ($hazardPct / 100), 2),
            'basic_pay'     => round($computedTax * ($basicPct / 100), 2),
            'longevity_pay' => round($computedTax * ($longevityPct / 100), 2),
        ];

        // Reconcile annual to match tax exactly
        $annualRecon = $reconcile($annualParts, $computedTax, 'hazard_pay');
        $annualParts = $annualRecon['parts'];
        $annualTotal = $annualRecon['total'];
        $annualUnallocated = round(max(0, $computedTax - $annualTotal), 2); // should be 0.00

        // Monthly parts (rounded from annual parts / 12)
        $monthlyParts = [
            'hazard_pay'    => round($annualParts['hazard_pay'] / 12, 2),
            'basic_pay'     => round($annualParts['basic_pay'] / 12, 2),
            'longevity_pay' => round($annualParts['longevity_pay'] / 12, 2),
        ];

        // Reconcile monthly to match monthly_tax exactly
        $monthlyRecon = $reconcile($monthlyParts, $monthlyTax, 'hazard_pay');
        $monthlyParts = $monthlyRecon['parts'];
        $monthlyTotal = $monthlyRecon['total'];
        $monthlyUnallocated = round(max(0, $monthlyTax - $monthlyTotal), 2); // should be 0.00

        // ----------------------------
        // 8) Fill response
        // ----------------------------
        $res['fixed_tax']     = $fixedTax;
        $res['tax_rate']      = $taxRate;
        $res['excess_over']   = $excessOver;
        $res['excess_amount'] = $excess;

        $res['tax']         = $computedTax;
        $res['monthly_tax'] = $monthlyTax;

        $res['allocation']['annual'] = [
            'hazard_pay'    => $annualParts['hazard_pay'],
            'basic_pay'     => $annualParts['basic_pay'],
            'longevity_pay' => $annualParts['longevity_pay'],
            'total'         => $annualTotal,
            'unallocated'   => $annualUnallocated,
        ];

        $res['allocation']['monthly'] = [
            'hazard_pay'    => $monthlyParts['hazard_pay'],
            'basic_pay'     => $monthlyParts['basic_pay'],
            'longevity_pay' => $monthlyParts['longevity_pay'],
            'total'         => $monthlyTotal,
            'unallocated'   => $monthlyUnallocated,
        ];

        $res['bracket'] = [
            'from' => $selectedBracket->income_from,
            'to'   => $selectedBracket->income_to,
        ];

        $res['remarks'] = 'Computed using TRAIN Law.';

        return $res;
    }

    private function getTrainLaw($train_law_id)
    {
        return DB::table('train_law_items')
            ->where('train_law_id', $train_law_id)
            ->get();
    }

    private function getEmployeeStartAndEndDate(string $employeeNo): array
    {
        $employee = DB::table('employee_information')
            ->select('date_hired_organization', 'date_resigned')
            ->where('employee_no', $employeeNo)
            ->first();

        if (!$employee) {
            throw new HttpException(404, "Employee not found: {$employeeNo}");
        }

        return [
            'start_date' => $employee->date_hired_organization,
            'end_date'   => $employee->date_resigned, // can be null
        ];
    }
}
