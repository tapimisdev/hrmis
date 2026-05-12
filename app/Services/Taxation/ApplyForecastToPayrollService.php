<?php

namespace App\Services\Taxation;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApplyForecastToPayrollService
{
    public function handle(int $taxationId, string $type): array
    {
        return DB::transaction(function () use ($taxationId, $type) {
            $taxation = DB::table('taxations')
                ->where('id', $taxationId)
                ->where('is_active', true)
                ->first();

            if (!$taxation) {
                throw new RuntimeException('Selected taxation record was not found or is inactive.');
            }

            $year = (int) $taxation->year;
            $months = $this->resolveMonthsForType($type);

            $employees = DB::table('taxation_employees as te')
                ->select(
                    'te.employee_no',
                    'te.amount_portion_basic_pay',
                    'te.amount_portion_hazard_pay',
                    'te.amount_portion_longevity_pay'
                )
                ->where('te.taxation_id', $taxation->id)
                ->where('te.year', $year)
                ->where('te.type', $type)
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
                throw new RuntimeException("No saved {$type} employee computations found for {$year}.");
            }

            $componentYearIds = [
                'salary' => $this->resolvePayrollComponentYearId((int) $taxation->salary_tax_id, $year, 'salary'),
                'hazard' => $this->resolvePayrollComponentYearId((int) $taxation->hazard_tax_id, $year, 'hazard pay'),
                'longevity' => $this->resolvePayrollComponentYearId((int) $taxation->longevity_id, $year, 'longevity'),
            ];

            $updatedRows = 0;
            $skippedRows = 0;

            foreach ($employees as $employee) {
                $monthlyAmounts = [
                    'salary' => round((float) $employee->amount_portion_basic_pay, 2),
                    'hazard' => round((float) $employee->amount_portion_hazard_pay, 2),
                    'longevity' => round((float) $employee->amount_portion_longevity_pay, 2),
                ];

                foreach ($months as $month) {
                    foreach ($componentYearIds as $key => $componentYearId) {
                        if ($this->hasProtectedPayroll($key, (string) $employee->employee_no, $year, $month)) {
                            $skippedRows++;
                            continue;
                        }

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
                'type' => $type,
                'months' => $months,
                'employee_count' => $employees->count(),
                'updated_rows' => $updatedRows,
                'skipped_rows' => $skippedRows,
            ];
        });
    }

    private function resolveMonthsForType(string $type): array
    {
        return match ($type) {
            'forecast' => range(1, 12),
            'q2' => range(4, 12),
            'q3' => range(7, 12),
            'q4' => range(10, 12),
            'nov' => [12],
            default => range(1, 12),
        };
    }

    private function hasProtectedPayroll(string $componentType, string $employeeNo, int $year, int $month): bool
    {
        return match ($componentType) {
            'salary' => $this->hasProtectedSalaryPayroll($employeeNo, $year, $month),
            'hazard' => $this->hasProtectedHazardPayroll($employeeNo, $year, $month),
            'longevity' => $this->hasProtectedLongevityPayroll($employeeNo, $year, $month),
            default => false,
        };
    }

    private function hasProtectedSalaryPayroll(string $employeeNo, int $year, int $month): bool
    {
        return DB::table('payroll_salary_permanent_employees as pspe')
            ->join('payroll_salary as ps', 'pspe.payroll_salary_id', '=', 'ps.id')
            ->where('pspe.employee_no', $employeeNo)
            ->whereYear('ps.payroll_date', $year)
            ->whereMonth('ps.payroll_date', $month)
            ->exists();
    }

    private function hasProtectedHazardPayroll(string $employeeNo, int $year, int $month): bool
    {
        return DB::table('payroll_hazard_pay_employee as phpe')
            ->join('payroll_hazard_pay as php', 'phpe.payroll_hazard_pay_id', '=', 'php.id')
            ->where('phpe.employee_no', $employeeNo)
            ->whereRaw('YEAR(CONCAT(php.month, "-01")) = ?', [$year])
            ->whereRaw('MONTH(CONCAT(php.month, "-01")) = ?', [$month])
            ->exists();
    }

    private function hasProtectedLongevityPayroll(string $employeeNo, int $year, int $month): bool
    {
        return DB::table('payroll_longevity_pay_employee as plpe')
            ->join('payroll_longevity_pay as plp', 'plpe.payroll_longevity_pay_id', '=', 'plp.id')
            ->where('plpe.employee_no', $employeeNo)
            ->whereRaw('YEAR(CONCAT(plp.month, "-01")) = ?', [$year])
            ->whereRaw('MONTH(CONCAT(plp.month, "-01")) = ?', [$month])
            ->exists();
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
