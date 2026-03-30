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
        return DB::transaction(function () use (
            $payload,
            $taxationId,
            $employeeNo,
            $computedAnnualTaxableAmounts
        ) {
            // Replace the prior snapshot only once the new recompute is ready to persist.
            DB::table('taxation_employees')
                ->where('taxation_id', $taxationId)
                ->where('employee_no', $employeeNo)
                ->delete();

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

            'amount_basic_salary'   => (float) data_get($payload, 'amounts.amount_basic_salary', 0),
            'months_covered'        => (float) data_get($payload, 'months_covered', 0),
            'amount_anual_total_basic_salary'   => (float) data_get($payload, 'amounts.amount_anual_total_basic_salary', 0),
            
            'amount_mid_year_bonus' => (float) data_get($payload, 'amounts.midYearBonus', 0),
            'amount_year_end_bonus' => (float) data_get($payload, 'amounts.yearEndBonus', 0),
            'amount_longevity_pay'  => (float) data_get($payload, 'amounts.longevityPay', 0),
            'amount_hazard_pay'     => (float) data_get($payload, 'amounts.hazardPay', 0),
            
            'amount_other_earnings_taxable'     => (float) data_get($payload, 'amounts.otherEarningsTaxable', 0),
            'amount_other_earnings_non_taxable' => (float) data_get($payload, 'amounts.otherEarningsNonTaxable', 0),

            'amount_gross'          => (float) data_get($payload, 'amounts.gross', 0),

            'amount_total_bonuses'     => (float) data_get($payload, 'amounts.amount_total_bonuses', 0),
            'amount_bonuses_exempt'     => (float) data_get($payload, 'amounts.amount_bonuses_exempt', 0),

            'amount_other_deductions' => (float) data_get($payload, 'amounts.otherDeductions', 0),
            'amount_annual_total_allowables' => (float) data_get($payload, 'amounts.annualTotalAllowables', 0),

            'amount_annual_taxable' => (float) data_get($payload, 'amounts.annualTaxable', 0),
            'amount_annual_tax'     => (float) data_get($payload, 'amounts.annualTax', 0),
            'amount_monthly_tax'    => (float) data_get($payload, 'amounts.monthlyTax', 0),

            'amount_portion_hazard_pay' => (float) data_get($payload, 'amounts.portionHazardPay', 0),
            'amount_portion_basic_pay' => (float) data_get($payload, 'amounts.portionBasicPay', 0),
            'amount_portion_longevity_pay' => (float) data_get($payload, 'amounts.portionLongevityPay', 0),

            'remarks'          => json_encode($payload['remarks'] ?? []),
            'raw_payload'           => json_encode($payload),
            'is_active'             => true,
            'created_at'            => now(),
            'updated_at'            => now(),
            ]);

            // Employee Others Earnings
            $earnings = collect(data_get($payload, 'othersEarnings', []))
                ->filter(fn($r) => filled(data_get($r, 'name')))
                ->map(fn($r) => [
                    'taxation_employee_id' => $taxationEmployeeId,
                    'is_default'           => $r['is_default'] ?? false,
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
                    'is_default'           => $r['is_default'] ?? false,
                    'name'                 => trim($r['name']),
                    'amount'               => (int) ($r['amount'] ?? 0),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ])
                ->all();

            if (!empty($deductions)) {
                DB::table('taxation_employee_other_deductions')->insert($deductions);
            }

            if (!empty($payload['midyear'])) {
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

            if (!empty($payload['year_end'])) {
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
                'bracket_from'         => (float) data_get($computedAnnualTaxableAmounts, 'bracket.from', 0),
                'bracket_to'           => (float) data_get($computedAnnualTaxableAmounts, 'bracket.to', 0),

                'remarks'              => data_get($computedAnnualTaxableAmounts, 'remarks', ''),

                'raw_payload'          => json_encode($computedAnnualTaxableAmounts),

                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            foreach ($payload['computations'] as $computation) {

            

                // Guard: skip anything not an array computation object
                if (!is_array($computation)) {
                    continue;
                }

                // Guard: must have a key
                if (!isset($computation['key'])) {
                    continue;
                }

                DB::table('taxation_employee_computations')->insert([
                    'taxation_employee_id' => $taxationEmployeeId,
                    'type'                 => $computation['key'], // safe now
                    'raw_computation'      => json_encode($computation, JSON_UNESCAPED_UNICODE),
                    'amount'               => (float) ($computation['result_raw']
                                            ?? str_replace(',', '', (string) ($computation['result'] ?? 0))),
                ]);
            }

            // dd($payload['computations']);

            return $taxationEmployeeId;
        });
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
                'months'                => [],
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

                // computation breakdown for UI accordions
                'computations'          => [],

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

            // computations still returned (empty)
            $res['computations'] = [
                [
                    'key' => 'basic_pay_annual',
                    'label' => 'Basic Pay (Annual Total)',
                    'formula' => 'latest_monthly_salary × months_covered',
                    'inputs' => [],
                    'steps' => [],
                    'result' => 0.0,
                ],
            ];

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

            $res['computations'] = [
                [
                    'key' => 'period',
                    'label' => 'Employment Period (Clamped)',
                    'formula' => 'clamp employment dates to the year window',
                    'inputs' => [
                        'raw_start' => $rawStart,
                        'raw_end' => $rawEnd,
                        'year_start' => $yearStart->toDateString(),
                        'year_end' => $yearEnd->toDateString(),
                    ],
                    'steps' => [
                        ['label' => 'Parsed start', 'value' => Carbon::parse($rawStart)->toDateString()],
                        ['label' => 'Parsed end (or year end if null)', 'value' => $rawEnd ? Carbon::parse($rawEnd)->toDateString() : $yearEnd->toDateString()],
                        ['label' => 'Clamped start', 'value' => $res['period_start']],
                        ['label' => 'Clamped end', 'value' => $res['period_end']],
                        ['label' => 'Validation', 'value' => 'end < start → no computable period'],
                    ],
                    'result' => null,
                ],
            ];

            return $res;
        }

        // ----------------------------
        // 4) Months covered (simple)
        // ----------------------------
        $monthsCovered = (($end->year - $start->year) * 12)
            + ($end->month - $start->month)
            + 1;

        $res['months_covered'] = (int) max(0, $monthsCovered);

        // build month labels for UI
        $monthList = [];
        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $monthList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $res['months'] = $monthList;

        // ----------------------------
        // 5) Latest salary as-of period_end
        // ----------------------------
        $latestSalaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $end->toDateString())
            ->orderBy('effectivity_date', 'desc')
            ->first();

        $latestMonthlySalary = (float) str_replace(',', '', (string) ($latestSalaryRow->amount ?? 0));

        $res['monthly_salary']        = (float) round($latestMonthlySalary, 4);
        $res['salary_effective_date'] = $latestSalaryRow->effectivity_date ?? null;

        // Annual total now uses latest salary * months covered
        $annualTotal = $latestMonthlySalary * $res['months_covered'];
        $res['annual_total'] = (float) round($annualTotal, 4);

        $monthlyBreakdown = array_map(function ($m) use ($res) {
            return [
                'month'  => $m,
                'amount' => (float) $res['monthly_salary'],
            ];
        }, $res['months']);

        $res['computations'] = [
            [
                'key'     => 'basic_salary',
                'label'   => 'Basic Salary (Annualized)',
                'formula' => 'Monthly Salary × Months Covered',
                'months'  => $monthlyBreakdown,
                'inputs'  => [
                    'monthly_salary'   => $res['monthly_salary'],
                    'months_covered'   => $res['months_covered'],
                    'effective_date'   => $res['salary_effective_date'],
                ],
                'steps'   => [
                    [
                        'label' => 'Latest Monthly Salary',
                        'value' => number_format($res['monthly_salary'], 4)
                    ],
                    [
                        'label' => 'Months Covered',
                        'value' => $res['months_covered']
                    ],
                    [
                        'label' => 'Multiplication',
                        'value' => number_format($res['monthly_salary'], 4)
                            . ' × '
                            . $res['months_covered']
                    ],
                ],
                'result'  => number_format($res['annual_total'], 4),
                'result_raw'  => $res['annual_total'],
                'meta'    => [
                    'type' => 'basic_salary'
                ]
            ]
        ];

        // Hazard pay uses latest monthly salary (15%)
        $monthlyHazardAmount = (float) round($latestMonthlySalary * 0.15, 4);
        $res['hazard_pay'] = $monthlyHazardAmount;

        $hazardMonthlyBreakdown = array_map(function ($m) use ($monthlyHazardAmount) {
            return [
                'month'  => $m,
                'amount' => $monthlyHazardAmount,
            ];
        }, $res['months']);

        $res['computations'][] = [
            'key'     => 'hazard_pay',
            'label'   => 'Hazard Pay',
            'formula' => 'Monthly Salary × 15%',
            'months'  => $hazardMonthlyBreakdown,
            'inputs'  => [
                'months_covered'   => $res['months_covered'],
                'effective_date'   => $res['salary_effective_date'],
                'monthly_salary' => $res['monthly_salary'],
                'rate'           => 0.15,
                'rate_percent'   => 15,
            ],
            'steps'   => [
                [
                    'label' => 'Latest Monthly Salary',
                    'value' => number_format($res['monthly_salary'], 4)
                ],
                [
                    'label' => 'Hazard Rate',
                    'value' => '15%'
                ],
                [
                    'label' => 'Multiplication',
                    'value' => number_format($res['monthly_salary'], 4) . ' × 15%'
                ],
            ],
            'result'  => number_format($res['hazard_pay'], 4),
            'result_raw' => $res['hazard_pay'],
            'meta'    => [
                'type' => 'hazard_pay'
            ]
        ];

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
            'basic_salary_as_of'    => (float) round($midyearBasicSalary, 4),
            'salary_effective_date' => $midyearSalaryRow->effectivity_date ?? null,

            'eligible'              => (bool) ($midyearEligibility['eligible'] ?? false),
            'months_of_service'     => (int) ($midyearEligibility['months_of_service'] ?? 0),
            'service_start'         => $midyearEligibility['service_start'] ?? null,
            'service_end'           => $midyearEligibility['service_end'] ?? null,

            'amount'                => (float) round(($midyearComputation['amount'] ?? 0), 4),
            'reason'                => $midyearComputation['reason'] ?? null,
        ];

        $res['computations'][] = [
            'key'     => 'mid_year',
            'label'   => 'Midyear Bonus',
            'formula' => $midyearComputation['formula'] ?? 'Based on midyear basic salary and months of service',
            'inputs'  => [
                'as_of_date'            => $midyearAsOf->toDateString(),
                'basic_salary_as_of'    => (float) round($midyearBasicSalary, 4),

                // Eligibility inputs
                'eligible'              => (bool) ($midyearEligibility['eligible'] ?? false),
                'months_of_service'     => (int) ($midyearEligibility['months_of_service'] ?? 0),
                'eligibility_reason'    => $midyearEligibility['reason'] ?? null,
            ],
            'steps' => array_values(array_filter([
                [
                    'label' => 'Salary reference date (as of)',
                    'value' => $midyearAsOf->toDateString(),
                ],
                [
                    'label' => 'Midyear basic salary used',
                    'value' => number_format((float) $midyearBasicSalary, 2),
                ],
                [
                    'label' => 'Months of service (eligibility basis)',
                    'value' => (int) ($midyearEligibility['months_of_service'] ?? 0),
                ],
                [
                    'label' => 'Eligibility',
                    'value' => ($midyearEligibility['eligible'] ?? false) ? 'Eligible' : 'Not eligible',
                ],
                // If your computeMidYearBonusAmount provides steps, include them
                !empty($midyearComputation['steps']) ? [
                    'label' => 'Computation steps',
                    'value' => $midyearComputation['steps'], // can be array; UI can render nested
                ] : null,

                // If not eligible, show reason clearly
                !($midyearEligibility['eligible'] ?? false) ? [
                    'label' => 'Reason',
                    'value' => $midyearEligibility['reason'] ?? 'Not eligible.',
                ] : null,
            ])),
            'result_raw' => (float) round((float) ($midyearComputation['amount'] ?? 0), 4),
            'result'     => number_format((float) ($midyearComputation['amount'] ?? 0), 2),

            'meta' => [
                'type' => 'mid_year',
                'salary_effective_date_used' => $midyearSalaryRow->effectivity_date ?? null,
            ],
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
            'basic_salary_as_of'    => (float) round($yearEndBasicSalary, 4),
            'salary_effective_date' => $yearEndSalaryRow->effectivity_date ?? null,

            'eligible'              => (bool) ($yearEndEligibility['eligible'] ?? false),
            'months_of_service'     => (int) ($yearEndEligibility['months_of_service'] ?? 0),
            'service_start'         => $yearEndEligibility['service_start'] ?? null,
            'service_end'           => $yearEndEligibility['service_end'] ?? null,

            'amount'                => (float) round(($yearEndComputation['amount'] ?? 0), 4),
            'reason'                => $yearEndComputation['reason'] ?? null,
        ];

        $res['computations'][] = [
            'key'     => 'year_end',
            'label'   => 'Year-End Bonus',
            'formula' => $yearEndComputation['formula'] ?? 'Based on year-end basic salary and months of service',
            'inputs'  => [
                // Salary reference
                'as_of_date'            => $yearEndAsOf->toDateString(),
                'basic_salary_as_of'    => (float) round($yearEndBasicSalary, 4),
                'salary_effective_date' => $yearEndSalaryRow->effectivity_date ?? null,

                // Eligibility details
                'eligible'          => (bool) ($yearEndEligibility['eligible'] ?? false),
                'months_of_service' => (int) ($yearEndEligibility['months_of_service'] ?? 0),
                'service_start'     => $yearEndEligibility['service_start'] ?? null,
                'service_end'       => $yearEndEligibility['service_end'] ?? null,
                'eligibility_reason'=> $yearEndEligibility['reason'] ?? null,
            ],
            'steps' => array_values(array_filter([
                [
                    'label' => 'Salary reference date (as of)',
                    'value' => $yearEndAsOf->toDateString(),
                ],
                [
                    'label' => 'Year-end basic salary used',
                    'value' => number_format((float) $yearEndBasicSalary, 2),
                ],
                [
                    'label' => 'Salary effective date used',
                    'value' => $yearEndSalaryRow->effectivity_date ?? null,
                ],
                [
                    'label' => 'Service period considered',
                    'value' => trim(
                        ($yearEndEligibility['service_start'] ?? '')
                        . ' → '
                        . ($yearEndEligibility['service_end'] ?? '')
                    ) ?: null,
                ],
                [
                    'label' => 'Months of service (eligibility basis)',
                    'value' => (int) ($yearEndEligibility['months_of_service'] ?? 0),
                ],
                [
                    'label' => 'Eligibility',
                    'value' => ($yearEndEligibility['eligible'] ?? false) ? 'Eligible' : 'Not eligible',
                ],

                // If computeYearEndBonusAmount provides steps, keep it (nested) or you can merge later
                !empty($yearEndComputation['steps']) ? [
                    'label' => 'Computation steps',
                    'value' => $yearEndComputation['steps'],
                ] : null,

                // Not eligible? show reason clearly
                !($yearEndEligibility['eligible'] ?? false) ? [
                    'label' => 'Reason',
                    'value' => $yearEndEligibility['reason'] ?? 'Not eligible.',
                ] : null,
            ])),
            'result_raw' => (float) round((float) ($yearEndComputation['amount'] ?? 0), 4),
            'result'     => number_format((float) ($yearEndComputation['amount'] ?? 0), 2),

            'meta' => [
                'type' => 'year_end',
            ],
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

        $amount = round($basicSalaryAsOfOct31, 4);

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
        $amount = round($monthlyBasicSalary, 4);

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
        $year = 2026
    ): array {

        $longevity = TableSettingsEnum::LONGETIVITY->value;

        $longevityData = $this->getComponentAmount($longevity, $employee_no, $year);

        $longevityTotal = $longevityData['total'];

        return [
            'employee_no'        => $employee_no,
            'year'               => $year,
            'longevity_total'    => $longevityTotal,
            'avg_monthly' => round(((float) $longevityTotal / 12), 4),
            'computations' => [$longevityData['computation']]
        ];
    }

    public function getAllowablesDeductions($employee_no, $year, $months_covered = 12): array
    {
        $months_covered = max(1, (int) $months_covered);

        $monthsMap = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

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

        // Helper: init a 12-month structure
        $initMonthly = function () use ($monthsMap) {
            $out = [];
            foreach ($monthsMap as $m => $label) {
                $out[$m] = [
                    'month'  => $m,
                    'label'  => $label,
                    'amount' => 0.0,
                ];
            }
            return $out;
        };

        // Group by module
        $grouped = $rows->groupBy('module_tab_id');

        // ----------------------------
        // GSIS (normal sum per month)
        // ----------------------------
        $gsisMonthly = $initMonthly();
        foreach (($grouped->get($gisId, collect()) ?? collect())->groupBy('month') as $m => $items) {
            $m = (int) $m;
            if ($m >= 1 && $m <= 12) {
                $gsisMonthly[$m]['amount'] = (float) round((float) $items->sum('amount'), 4);
            }
        }
        $gsisTotal = (float) round(array_sum(array_column($gsisMonthly, 'amount')), 4);

        // ----------------------------
        // PhilHealth (normal sum per month)
        // ----------------------------
        $philMonthly = $initMonthly();
        foreach (($grouped->get($philhealthId, collect()) ?? collect())->groupBy('month') as $m => $items) {
            $m = (int) $m;
            if ($m >= 1 && $m <= 12) {
                $philMonthly[$m]['amount'] = (float) round((float) $items->sum('amount'), 4);
            }
        }
        $philhealthTotal = (float) round(array_sum(array_column($philMonthly, 'amount')), 4);

        // ----------------------------
        // Pag-IBIG (cap ₱200 per month)
        // ----------------------------
        $pagibigMonthly = $initMonthly();
        foreach (($grouped->get($pagibigId, collect()) ?? collect())->groupBy('month') as $m => $items) {
            $m = (int) $m;
            if ($m >= 1 && $m <= 12) {
                $rawMonthly = (float) $items->sum('amount');
                $capped     = min($rawMonthly, 200.00);
                $pagibigMonthly[$m]['amount'] = (float) round((float) $capped, 4);
            }
        }
        $pagibigTotal = (float) round(array_sum(array_column($pagibigMonthly, 'amount')), 4);

        // For UI steps: month lists
        $toMonthSteps = function (array $monthly) {
            $steps = [];
            foreach ($monthly as $m) {
                $steps[] = [
                    'label' => $m['label'],
                    'value' => number_format((float) $m['amount'], 2),
                ];
            }
            return $steps;
        };

        // Build computation payload (single combined "allowables")
        $computation = [
            'key'     => 'allowables_deductions',
            'label'   => 'Allowable Deductions (GSIS / Pag-IBIG / PhilHealth)',
            'formula' => 'GSIS = SUM(months), PhilHealth = SUM(months), Pag-IBIG = SUM(min(month_total, 200))',
            'inputs'  => [
                'employee_no'    => $employee_no,
                'year'           => (int) $year,
                'months_covered' => $months_covered,
                'modules' => [
                    'gsis' => [
                        'module_tab_id' => $gisId,
                        'monthly'       => array_values($gsisMonthly),
                        'total'         => $gsisTotal,
                    ],
                    'pagibig' => [
                        'module_tab_id' => $pagibigId,
                        'cap_per_month' => 200.00,
                        'monthly'       => array_values($pagibigMonthly),
                        'total'         => $pagibigTotal,
                    ],
                    'philhealth' => [
                        'module_tab_id' => $philhealthId,
                        'monthly'       => array_values($philMonthly),
                        'total'         => $philhealthTotal,
                    ],
                ],
            ],
            'steps' => [
                [
                    'label' => 'GSIS monthly (Jan–Dec)',
                    'value' => $toMonthSteps($gsisMonthly),
                ],
                [
                    'label' => 'GSIS total',
                    'value' => number_format((float) $gsisTotal, 2),
                ],
                [
                    'label' => 'PhilHealth monthly (Jan–Dec)',
                    'value' => $toMonthSteps($philMonthly),
                ],
                [
                    'label' => 'PhilHealth total',
                    'value' => number_format((float) $philhealthTotal, 2),
                ],
                [
                    'label' => 'Pag-IBIG monthly (capped at ₱200 per month)',
                    'value' => $toMonthSteps($pagibigMonthly),
                ],
                [
                    'label' => 'Pag-IBIG total (sum of capped months)',
                    'value' => number_format((float) $pagibigTotal, 2),
                ],
                [
                    'label' => 'Total allowables',
                    'value' => number_format((float) ($gsisTotal + $pagibigTotal + $philhealthTotal), 2),
                ],
            ],
            'result_raw' => (float) round((float) ($gsisTotal + $pagibigTotal + $philhealthTotal), 4),
            'result'     => number_format((float) ($gsisTotal + $pagibigTotal + $philhealthTotal), 2),
            'meta' => [
                'type' => 'allowables',
            ],
        ];

        return [
            'gsis'       => (float) round($gsisTotal, 4),
            'pagibig'    => (float) round($pagibigTotal, 4),
            'philhealth' => (float) round($philhealthTotal, 4),

            // optional for UI
            'monthly' => [
                'gsis'       => array_values($gsisMonthly),
                'pagibig'    => array_values($pagibigMonthly),
                'philhealth' => array_values($philMonthly),
            ],

            // merge into $res['computations'][]
            'computations' => $computation,
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
            $sum = round(array_sum($parts), 4);
            $diff = round($expectedTotal - $sum, 4);

            if ($diff != 0.00) {
                if (!array_key_exists($adjustKey, $parts)) {
                    $adjustKey = array_key_first($parts); // fallback
                }
                $parts[$adjustKey] = round($parts[$adjustKey] + $diff, 4);
            }

            // recompute total after adjustment
            $sumAfter = round(array_sum($parts), 4);

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
        $hazardPct    = round((float) ($allocations['hazardPayPct'] ?? 0), 4);
        $basicPct     = round((float) ($allocations['basicPayPct'] ?? 0), 4);
        $longevityPct = round((float) ($allocations['longevityPct'] ?? 0), 4);

        $totalPct = round($hazardPct + $basicPct + $longevityPct, 4);
        $unallocatedPct = round(max(0, 100 - $totalPct), 4);

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
        $annual_taxable = round((float) $annual_taxable, 4);
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
        $fixedTax   = round((float) $selectedBracket->fixed_tax, 4);
        $taxRate    = round((float) $selectedBracket->tax_rate, 4);
        $excessOver = round((float) $selectedBracket->excess_over, 4);

        $excess = round(max(0, $annual_taxable - $excessOver), 4);

        $computedTax = $fixedTax + ($excess * ($taxRate / 100));
        $computedTax = round($computedTax, 4);

        $monthlyTax = round($computedTax / 12, 4);

        // ----------------------------
        // 7) Allocation amounts with reconciliation
        // ----------------------------

        // Annual parts (rounded)
        $annualParts = [
            'hazard_pay'    => round($computedTax * ($hazardPct / 100), 4),
            'basic_pay'     => round($computedTax * ($basicPct / 100), 4),
            'longevity_pay' => round($computedTax * ($longevityPct / 100), 4),
        ];

        // Reconcile annual to match tax exactly
        $annualRecon = $reconcile($annualParts, $computedTax, 'hazard_pay');
        $annualParts = $annualRecon['parts'];
        $annualTotal = $annualRecon['total'];
        $annualUnallocated = round(max(0, $computedTax - $annualTotal), 4); // should be 0.00

        // Monthly parts (rounded from annual parts / 12)
        $monthlyParts = [
            'hazard_pay'    => round($annualParts['hazard_pay'] / 12, 4),
            'basic_pay'     => round($annualParts['basic_pay'] / 12, 4),
            'longevity_pay' => round($annualParts['longevity_pay'] / 12, 4),
        ];

        // Reconcile monthly to match monthly_tax exactly
        $monthlyRecon = $reconcile($monthlyParts, $monthlyTax, 'hazard_pay');
        $monthlyParts = $monthlyRecon['parts'];
        $monthlyTotal = $monthlyRecon['total'];
        $monthlyUnallocated = round(max(0, $monthlyTax - $monthlyTotal), 4); // should be 0.00

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

    public function getNonTaxableAllowance($employee_no, $year): array
    {
        $allowances = [
            'pera' => TableSettingsEnum::PERA->value,
            'representation_allowance' => TableSettingsEnum::REPRESENTATION_ALLOWANCE->value,
            'transportation_allowance' => TableSettingsEnum::TRANSPORTATION_ALLOWANCE->value,
        ];

        $results = [];

        foreach ($allowances as $name => $type) {

            $data = $this->getComponentAmount($type, $employee_no, $year);
            
            $results[] = [
                'is_default' => true,
                'name' => ucfirst(str_replace('_', ' ', $name)),
                'tax_type' => 'non_taxable',
                'amount' => $data['total'],
                'computations' => [$data['computation']]
            ];
        }

        return $results;
    }

    private function getComponentAmount($type, $employee_no, $year, $months_covered = 12): array
    {
        $months_covered = max(1, (int) $months_covered);

        $monthsMap = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // Get component ID from settings
        $componentId = DB::table('payroll_components_settings')
            ->where('type', $type)
            ->value('table_id');

        if (!$componentId) {
            return [
                'total' => 0.00,
                'monthly' => array_values(array_map(fn($label) => ['label' => $label, 'amount' => 0.0], $monthsMap)),
                'avg_monthly' => 0.00,
                'computation' => [
                    'key'   => $type,
                    'label' => ucfirst(str_replace('_', ' ', $type)),
                    'formula' => 'Component not found in payroll_components_settings',
                    'inputs' => [
                        'type' => $type,
                        'year' => (int) $year,
                        'months_covered' => $months_covered,
                    ],
                    'steps' => [
                        ['label' => 'Lookup payroll_components_settings', 'value' => 'No component found'],
                    ],
                    'result_raw' => 0.00,
                    'result'     => number_format(0, 2),
                    'meta' => [
                        'type' => $type,
                        'is_missing' => true,
                    ],
                ],
            ];
        }

        // Get component year ID
        $componentYearId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentId)
            ->where('year', $year)
            ->value('id');

        if (!$componentYearId) {
            return [
                'total' => 0.00,
                'monthly' => array_values(array_map(fn($label) => ['label' => $label, 'amount' => 0.0], $monthsMap)),
                'avg_monthly' => 0.00,
                'computation' => [
                    'key'   => $type,
                    'label' => ucfirst(str_replace('_', ' ', $type)),
                    'formula' => 'Component year not configured',
                    'inputs' => [
                        'component_id' => $componentId,
                        'year'         => (int) $year,
                        'months_covered' => $months_covered,
                    ],
                    'steps' => [
                        ['label' => 'Lookup payroll_components_years', 'value' => "No configuration found for year {$year}"],
                    ],
                    'result_raw' => 0.00,
                    'result'     => number_format(0, 2),
                    'meta' => [
                        'type' => $type,
                        'is_missing' => true,
                    ],
                ],
            ];
        }

        // Monthly breakdown using month column (1..12)
        $rows = DB::table('employee_payroll_components')
            ->selectRaw('month, SUM(amount) as total')
            ->where('tax_deduction_id', $componentYearId)
            ->where('employee_no', $employee_no)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // init months with 0
        $monthly = [];
        foreach ($monthsMap as $m => $label) {
            $monthly[$m] = [
                'month'  => $m,
                'label'  => $label,
                'amount' => 0.0,
            ];
        }

        // fill
        foreach ($rows as $r) {
            $m = (int) ($r->month ?? 0);
            if ($m >= 1 && $m <= 12) {
                $monthly[$m]['amount'] = (float) round((float) ($r->total ?? 0), 4);
            }
        }

        // total = sum of monthly
        $total = 0.0;
        foreach ($monthly as $m) {
            $total += (float) $m['amount'];
        }
        $total = (float) round($total, 4);

        // avg based on months_covered (employment months)
        $avgMonthly = (float) round($total / $months_covered, 4);

        // steps for UI
        $monthSteps = [];
        foreach ($monthly as $m) {
            $monthSteps[] = [
                'label' => $m['label'],
                'value' => number_format((float) $m['amount'], 2),
            ];
        }

        // Build computation payload
        $computation = [
            'key'     => $type,
            'label'   => ucfirst(str_replace('_', ' ', $type)),
            'formula' => 'SUM(amount) per month; Total = SUM(months); Avg = Total ÷ Months Covered',
            'inputs'  => [
                'employee_no'           => $employee_no,
                'year'                  => (int) $year,
                'component_type'        => $type,
                'payroll_component_id'  => $componentId,
                'component_year_id'     => $componentYearId,
                'months_covered'        => $months_covered,
                'monthly'               => array_values($monthly), // raw monthly amounts
            ],
            'steps' => [
                [
                    'label' => 'Lookup component (settings)',
                    'value' => "Found payroll_component_id = {$componentId}",
                ],
                [
                    'label' => 'Lookup component year',
                    'value' => "Found payroll_components_years.id = {$componentYearId}",
                ],
                [
                    'label' => 'Monthly breakdown (Jan–Dec)',
                    'value' => $monthSteps, // nested list
                ],
                [
                    'label' => 'Total (sum of months)',
                    'value' => number_format((float) $total, 2),
                ],
                [
                    'label' => 'Average monthly (Total ÷ Months Covered)',
                    'value' => number_format((float) $total, 2) . ' ÷ ' . $months_covered,
                ],
            ],
            'result_raw' => (float) $total,                 // main result = total
            'result'     => number_format((float) $total, 2),
            'meta' => [
                'type'            => $type,
                'avg_monthly_raw' => (float) $avgMonthly,
            ],
        ];

        return [
            'total'       => (float) $total,
            'monthly'     => array_values($monthly),
            'avg_monthly' => (float) $avgMonthly,
            'computation' => $computation,
        ];
    }
}
