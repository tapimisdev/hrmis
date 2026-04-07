<?php

namespace App\Services\Taxation;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApplyForecastToPayrollService
{
    public function handle(int $taxationId): array
    {
        return DB::transaction(function () use ($taxationId) {
            $taxation = DB::table('taxations')
                ->where('id', $taxationId)
                ->where('is_active', true)
                ->first();

            if (!$taxation) {
                throw new RuntimeException('Selected taxation record was not found or is inactive.');
            }

            $year = (int) $taxation->year;

            $employees = DB::table('taxation_employees as te')
                ->select(
                    'te.employee_no',
                    'te.amount_portion_basic_pay',
                    'te.amount_portion_hazard_pay',
                    'te.amount_portion_longevity_pay'
                )
                ->where('te.taxation_id', $taxation->id)
                ->where('te.year', $year)
                ->where('te.is_active', true)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('taxation_employee_computations as tec')
                        ->whereColumn('tec.taxation_employee_id', 'te.id');
                })
                ->get()
                ->filter(fn ($employee) => filled($employee->employee_no))
                ->values();

            if ($employees->isEmpty()) {
                throw new RuntimeException("No saved forecast employee computations found for {$year}.");
            }

            $componentYearIds = [
                'salary' => $this->resolvePayrollComponentYearId((int) $taxation->salary_tax_id, $year, 'salary'),
                'hazard' => $this->resolvePayrollComponentYearId((int) $taxation->hazard_tax_id, $year, 'hazard pay'),
                'longevity' => $this->resolvePayrollComponentYearId((int) $taxation->longevity_id, $year, 'longevity'),
            ];

            $updatedRows = 0;

            foreach ($employees as $employee) {
                $monthlyAmounts = [
                    'salary' => round((float) $employee->amount_portion_basic_pay, 2),
                    'hazard' => round((float) $employee->amount_portion_hazard_pay, 2),
                    'longevity' => round((float) $employee->amount_portion_longevity_pay, 2),
                ];

                foreach (range(1, 12) as $month) {
                    foreach ($componentYearIds as $key => $componentYearId) {
                        DB::table('employee_payroll_components')->updateOrInsert(
                            [
                                'tax_deduction_id' => $componentYearId,
                                'employee_no' => $employee->employee_no,
                                'month' => $month,
                            ],
                            [
                                'amount' => $monthlyAmounts[$key],
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        $updatedRows++;
                    }
                }
            }

            return [
                'taxation_id' => $taxation->id,
                'year' => $year,
                'employee_count' => $employees->count(),
                'updated_rows' => $updatedRows,
            ];
        });
    }

    private function resolvePayrollComponentYearId(int $componentId, int $year, string $label): int
    {
        if ($componentId <= 0) {
            throw new RuntimeException("The selected {$label} tax table is not configured in the taxation record.");
        }

        $componentYearId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentId)
            ->where('year', $year)
            ->value('id');

        if (!$componentYearId) {
            throw new RuntimeException("No existing {$label} payroll table was found for taxation year {$year}.");
        }

        return (int) $componentYearId;
    }
}
