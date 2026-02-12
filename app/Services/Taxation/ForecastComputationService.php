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
        string $employeeNo
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

        return $taxationEmployeeId;
    }

    public function annualSalaryTotalByMonth(
        string $employeeNo,
        int $year = 2026,
    ): array {
        // Year window
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd   = Carbon::create($year, 12, 31)->endOfDay();

        $remarks = '';

        // Employee employment window (from DB)
        $empDates = $this->getEmployeeStartAndEndDate($employeeNo);

        // Prefer passed values, otherwise DB values
        $rawStart = $empDates['start_date'] ?? null;
        $rawEnd   = $empDates['end_date'] ?? null;

        if (!$rawStart) {
            return [
                'employee_no'   => $employeeNo,
                'year'          => $year,
                'period_start'  => null,
                'period_end'    => null,
                'segments'      => [],
                'annual_total'  => 0,
                'months_covered' => 0,
                'monthly_salary' => 0,
                'hazard_pay'    => 0,
                'midyear'       => [
                    'eligible' => false,
                    'amount'   => 0,
                    'reason'   => 'No date hired company.',
                ],
                'year_end'      => [
                    'eligible' => false,
                    'amount'   => 0,
                    'reason'   => 'No date hired company.',
                ],
                'remarks'       => 'No date hired company ',
            ];
        }

        // Employment window (parsed)
        $start = Carbon::parse($rawStart)->startOfDay();
        $end   = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : $yearEnd->copy();

        // Clamp to year window
        if ($start->lt($yearStart)) $start = $yearStart->copy();
        if ($end->gt($yearEnd))     $end   = $yearEnd->copy();

        // No months to compute
        if ($end->lt($start)) {
            return [
                'employee_no'   => $employeeNo,
                'year'          => $year,
                'period_start'  => $start->toDateString(),
                'period_end'    => $end->toDateString(),
                'segments'      => [],
                'annual_total'  => 0,
                'months_covered' => 0,
                'monthly_salary' => 0,
                'hazard_pay'    => 0,
                'midyear'       => [
                    'eligible' => false,
                    'amount'   => 0,
                    'reason'   => 'No computable period.',
                ],
                'year_end'      => [
                    'eligible' => false,
                    'amount'   => 0,
                    'reason'   => 'No computable period.',
                ],
            ];
        }

        // Salary active as of period start (could be from prior year)
        $base = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $start->toDateString())
            ->orderBy('effectivity_date', 'desc')
            ->first();

        // All salary changes that happen within the window
        $changes = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereBetween('effectivity_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('effectivity_date', 'asc')
            ->get();

        // Build timeline points
        $points = collect();

        $baseAmount = $base?->amount ?? 0;
        $baseAmount = (float) str_replace(',', '', (string) $baseAmount);

        // Start point at period start (uses base salary)
        $points->push((object) [
            'start_date'     => $start->toDateString(),
            'monthly_salary' => (float) ($baseAmount ?? 0),
            'source_date'    => $base?->effectivity_date ?? null,
        ]);

        // Each change becomes a new start point
        foreach ($changes as $c) {
            $cAmount = (float) str_replace(',', '', (string) ($c->amount ?? 0));
            $points->push((object) [
                'start_date'     => Carbon::parse($c->effectivity_date)->toDateString(),
                'monthly_salary' => (float) $cAmount,
                'source_date'    => $c->effectivity_date,
            ]);
        }

        // If duplicates same date, keep the last one
        $points = $points
            ->sortBy('start_date')
            ->groupBy('start_date')
            ->map(fn($g) => $g->last())
            ->values();

        $segments = [];
        $annualTotal = 0;

        for ($i = 0; $i < $points->count(); $i++) {
            $segStart = Carbon::parse($points[$i]->start_date)->startOfMonth();

            $segEnd = ($i < $points->count() - 1)
                ? Carbon::parse($points[$i + 1]->start_date)->startOfMonth()->subMonth()->endOfMonth()
                : $end->copy();

            if ($segStart->lt($start)) $segStart = $start->copy()->startOfMonth();
            if ($segEnd->gt($end))     $segEnd   = $end->copy();

            if ($segEnd->lt($segStart)) continue;

            $months = (($segEnd->year - $segStart->year) * 12)
                + ($segEnd->month - $segStart->month)
                + 1;

            $monthly = (float) $points[$i]->monthly_salary;
            $amount  = $monthly * $months;

            $segments[] = [
                'start'          => $segStart->toDateString(),
                'end'            => $segEnd->toDateString(),
                'months'         => $months,
                'monthly_salary' => $monthly,
                'segment_total'  => $amount,
            ];

            $annualTotal += $amount;
        }

        // FIXED monthly salary & hazard pay
        $monthsCovered = collect($segments)->sum('months');
        $avgMonthlySalary = $monthsCovered > 0 ? ($annualTotal / $monthsCovered) : 0;
        $hazardPayMonthly = $avgMonthlySalary * 0.15;

        /**
         * MIDYEAR
         */
        $midyearAsOf = Carbon::create($year, 5, 15)->endOfDay();

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
            $midyearComputation['amount'] = 0;
            $midyearComputation['reason'] = $midyearEligibility['reason'] ?? 'Not eligible.';
        }

        /**
         * YEAR-END (Forecast-ready; run in January)
         */
        $yearEndAsOf = Carbon::create($year, 10, 31)->endOfDay();

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
            $yearEndComputation['amount'] = 0;
            $yearEndComputation['reason'] = $yearEndEligibility['reason'] ?? 'Not eligible.';
        }

        return [
            'employee_no'     => $employeeNo,
            'year'            => $year,
            'period_start'    => $start->toDateString(),
            'period_end'      => $end->toDateString(),
            'segments'        => $segments,
            'annual_total'    => (float) round($annualTotal, 2),

            'months_covered'  => (int) $monthsCovered,
            'monthly_salary'  => (float) round($avgMonthlySalary, 2),
            'hazard_pay'      => (float) round($hazardPayMonthly, 2),

            'midyear'         => [
                'as_of'                 => $midyearAsOf->toDateString(),
                'basic_salary_as_of'    => round($midyearBasicSalary, 2),
                'salary_effective_date' => $midyearSalaryRow->effectivity_date ?? null,

                'eligible'              => (bool) ($midyearEligibility['eligible'] ?? false),
                'months_of_service'     => (int) ($midyearEligibility['months_of_service'] ?? 0),
                'service_start'         => $midyearEligibility['service_start'] ?? null,
                'service_end'           => $midyearEligibility['service_end'] ?? null,

                'amount'                => (float) round(($midyearComputation['amount'] ?? 0), 2),
                'factor'                => (float) ($midyearComputation['factor'] ?? 0),
                'prorated'              => (bool) ($midyearComputation['prorated'] ?? false),
                'reason'                => $midyearComputation['reason'] ?? null,
            ],

            'year_end'        => [
                'as_of'                 => $yearEndAsOf->toDateString(),
                'basic_salary_as_of'    => round($yearEndBasicSalary, 2),
                'salary_effective_date' => $yearEndSalaryRow->effectivity_date ?? null,

                'eligible'              => (bool) ($yearEndEligibility['eligible'] ?? false),
                'months_of_service'     => (int) ($yearEndEligibility['months_of_service'] ?? 0),
                'service_start'         => $yearEndEligibility['service_start'] ?? null,
                'service_end'           => $yearEndEligibility['service_end'] ?? null,

                'amount'                => (float) round(($yearEndComputation['amount'] ?? 0), 2),
                'factor'                => (float) ($yearEndComputation['factor'] ?? 0),
                'prorated'              => (bool) ($yearEndComputation['prorated'] ?? false),
                'reason'                => $yearEndComputation['reason'] ?? null,
            ],

            'remarks'         => $remarks,
        ];
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
        $asOf = Carbon::create($year, 10, 31)->endOfDay();

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
        int $train_law_id
    ): array {

        $annual_taxable = round((float) $annual_taxable, 2);

        // Guard
        if ($annual_taxable <= 0) {
            return [
                'employee_no'   => $employee_no,
                'annual_income' => $annual_taxable,
                'tax'           => 0.00,
                'bracket'       => null,
                'remarks'       => 'No taxable income.'
            ];
        }

        $brackets = $this->getTrainLaw($train_law_id);

        if ($brackets->isEmpty()) {
            return [
                'employee_no'   => $employee_no,
                'annual_income' => $annual_taxable,
                'tax'           => 0.00,
                'bracket'       => null,
                'remarks'       => 'No TRAIN law table found.'
            ];
        }

        $selectedBracket = null;

        foreach ($brackets as $item) {

            $incomeFrom = (float) $item->income_from;
            $incomeTo   = $item->income_to !== null
                ? (float) $item->income_to
                : null;

            // Last bracket (income_to is NULL)
            if (is_null($incomeTo)) {
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
            return [
                'employee_no'   => $employee_no,
                'annual_income' => $annual_taxable,
                'tax'           => 0.00,
                'bracket'       => null,
                'remarks'       => 'No matching tax bracket.'
            ];
        }

        $fixedTax   = (float) $selectedBracket->fixed_tax;
        $taxRate    = (float) $selectedBracket->tax_rate;
        $excessOver = (float) $selectedBracket->excess_over;

        $excess = max(0, $annual_taxable - $excessOver);

        $computedTax = $fixedTax + ($excess * ($taxRate / 100));

        $computedTax = round($computedTax, 2);

        return [
            'employee_no'   => $employee_no,
            'annual_income' => $annual_taxable,
            'fixed_tax'     => $fixedTax,
            'tax_rate'      => $taxRate,
            'excess_over'   => $excessOver,
            'excess_amount' => round($excess, 2),
            'tax'           => $computedTax,
            'bracket'       => [
                'from' => $selectedBracket->income_from,
                'to'   => $selectedBracket->income_to
            ],
            'remarks'       => 'Computed using TRAIN Law.'
        ];
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
