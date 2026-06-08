<?php

namespace App\Services;

use App\Enums\EmploymentTypesEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\DB;

class PayrollComponentService
{
    /**
     * Get all employee tax data for a specific tax and year.
     */
    public function getAll(int $component_id, int $year_id, string $employment_type = null)
    {
        $allowed_id = is_null($employment_type) || $employment_type === 'regular' 
            ? EmploymentTypesEnum::REGULAR->value 
            : EmploymentTypesEnum::COS->value;

        $monthNames = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        // Fetch the tax deduction record
        $taxDeduction = DB::table('payroll_components_years')
            ->where('payroll_component_id', $component_id)
            ->where('year', $year_id)
            ->first();


        if (!$taxDeduction) {
            return collect(); // Return empty collection if not found
        }

        $taxDeductionId = $taxDeduction->id;
        $componentType = DB::table('payroll_components_settings')
            ->where('tax_id', $component_id)
            ->value('type');

        // Fetch all regular employees with organization info
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('eo.employment_type_id', $allowed_id)
            ->select(
                'ei.employee_no',
                'ep.suffix',
                'ep.middlename',
                'ep.lastname',
                'ep.firstname',
                'd.code as division_code',
                'd.name as division_name'
            )
            ->orderBy('ep.lastname', 'asc')
            ->get();

        // Fetch all employee_payroll_components for this tax deduction in one query
        $employeeTaxes = DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $taxDeductionId)
            ->get()
            ->groupBy('employee_no');

        $employeeNos = $employees
            ->pluck('employee_no')
            ->filter()
            ->map(fn ($employeeNo) => (string) $employeeNo)
            ->values()
            ->all();

        $lockedByMonth = $this->getLockedEmployeesByMonth(
            $componentType,
            $year_id,
            $employment_type ?? 'regular',
            $employeeNos
        );

        // Map employee data with monthly tax amounts
        return $employees->map(function ($employee) use ($employeeTaxes, $taxDeductionId, $monthNames, $lockedByMonth) {
            $taxRecords = $employeeTaxes[$employee->employee_no] ?? [];

            // Initialize month values to 0
            foreach ($monthNames as $month => $monthName) {
                $record = collect($taxRecords)->firstWhere('month', $month);
                $employee->{$monthName} = $record->amount ?? 0;
                $employee->{$monthName . '_locked'} = in_array(
                    (string) $employee->employee_no,
                    $lockedByMonth[$month] ?? [],
                    true
                );
            }

            $employee->tax_deduction_id = $taxDeductionId;
            $employee->module_tab_id = $taxDeductionId;

            return $employee;
        });
    }

    private function getLockedEmployeesByMonth(?string $componentType, int $year, string $employmentType, array $employeeNos): array
    {
        $locked = [];
        for ($month = 1; $month <= 12; $month++) {
            $locked[$month] = [];
        }

        if (empty($employeeNos) || !$componentType) {
            return $locked;
        }

        if (
            $componentType === TableSettingsEnum::SALARY_ID->value ||
            in_array($componentType, ['ewt_2%', 'percentage_tax_3%', 'tax_ewt_5%'], true)
        ) {
            $rows = $employmentType === 'cos'
                ? DB::table('payroll_salary_employee as pse')
                    ->join('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                    ->selectRaw('pse.employee_no, MONTH(ps.payroll_date) as month_number')
                    ->whereIn('pse.employee_no', $employeeNos)
                    ->whereYear('ps.payroll_date', $year)
                    ->where(function ($query) {
                        $query->whereNull('ps.status')
                            ->orWhere('ps.status', '!=', 'draft');
                    })
                    ->distinct()
                    ->get()
                : DB::table('payroll_salary_permanent_employees as pspe')
                    ->join('payroll_salary as ps', 'pspe.payroll_salary_id', '=', 'ps.id')
                    ->selectRaw('pspe.employee_no, MONTH(ps.payroll_date) as month_number')
                    ->whereIn('pspe.employee_no', $employeeNos)
                    ->whereYear('ps.payroll_date', $year)
                    ->where(function ($query) {
                        $query->whereNull('ps.status')
                            ->orWhere('ps.status', '!=', 'draft');
                    })
                    ->distinct()
                    ->get();

            foreach ($rows as $row) {
                $monthNumber = (int) ($row->month_number ?? 0);
                if ($monthNumber >= 1 && $monthNumber <= 12) {
                    $locked[$monthNumber][] = (string) $row->employee_no;
                }
            }

            return $locked;
        }

        if ($componentType === TableSettingsEnum::HAZARD_PA->value) {
            $rows = DB::table('payroll_hazard_pay_employee as phpe')
                ->join('payroll_hazard_pay as php', 'phpe.payroll_hazard_pay_id', '=', 'php.id')
                ->selectRaw('phpe.employee_no, MONTH(CONCAT(php.month, "-01")) as month_number')
                ->whereIn('phpe.employee_no', $employeeNos)
                ->whereRaw('YEAR(CONCAT(php.month, "-01")) = ?', [$year])
                ->where(function ($query) {
                    $query->whereNull('php.status')
                        ->orWhere('php.status', '!=', 'draft');
                })
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $monthNumber = (int) ($row->month_number ?? 0);
                if ($monthNumber >= 1 && $monthNumber <= 12) {
                    $locked[$monthNumber][] = (string) $row->employee_no;
                }
            }
        }

        if ($componentType === TableSettingsEnum::LONGETIVITY->value) {
            $rows = DB::table('payroll_longevity_pay_employee as plpe')
                ->join('payroll_longevity_pay as plp', 'plpe.payroll_longevity_pay_id', '=', 'plp.id')
                ->selectRaw('plpe.employee_no, MONTH(CONCAT(plp.month, "-01")) as month_number')
                ->whereIn('plpe.employee_no', $employeeNos)
                ->whereRaw('YEAR(CONCAT(plp.month, "-01")) = ?', [$year])
                ->where(function ($query) {
                    $query->whereNull('plp.status')
                        ->orWhere('plp.status', '!=', 'draft');
                })
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $monthNumber = (int) ($row->month_number ?? 0);
                if ($monthNumber >= 1 && $monthNumber <= 12) {
                    $locked[$monthNumber][] = (string) $row->employee_no;
                }
            }
        }

        if (in_array($componentType, [
            TableSettingsEnum::PERA->value,
            TableSettingsEnum::REPRESENTATION_ALLOWANCE->value,
            TableSettingsEnum::TRANSPORTATION_ALLOWANCE->value,
        ], true)) {
            $rows = DB::table('payroll_pera_rata_employee as ppre')
                ->join('payroll_pera_rata as ppr', 'ppre.payroll_pera_rata_id', '=', 'ppr.id')
                ->selectRaw('ppre.employee_no, MONTH(CONCAT(ppr.month, "-01")) as month_number')
                ->whereIn('ppre.employee_no', $employeeNos)
                ->whereRaw('YEAR(CONCAT(ppr.month, "-01")) = ?', [$year])
                ->where(function ($query) {
                    $query->whereNull('ppr.status')
                        ->orWhere('ppr.status', '!=', 'draft');
                })
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $monthNumber = (int) ($row->month_number ?? 0);
                if ($monthNumber >= 1 && $monthNumber <= 12) {
                    $locked[$monthNumber][] = (string) $row->employee_no;
                }
            }
        }

        return $locked;
    }
}
