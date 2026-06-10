<?php

namespace App\Services\Taxation;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndividualTaxMonthlyReportService
{
    public function __construct(
        private readonly IndividualTaxDataService $individualTaxDataService,
        private readonly IndividualTaxAmountService $individualTaxAmountService
    ) {}

    public function getPagePayload(?int $month = null, ?int $year = null): array
    {
        $availableYears = $this->individualTaxDataService->getAvailableTaxationYears();
        $selectedYear = $this->individualTaxDataService->resolveSelectedYear($year, $availableYears);
        $selectedMonth = $this->resolveSelectedMonth($month);
        $taxationPayload = $this->individualTaxDataService->getPagePayload(null, $selectedYear);
        $employees = collect((array) ($taxationPayload['employees'] ?? []))
            ->map(fn ($employee) => (array) $employee)
            ->filter(fn (array $employee) => filled($employee['employee_no'] ?? null))
            ->values()
            ->all();

        return [
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'availableYears' => $availableYears
                ->push($selectedYear)
                ->unique()
                ->sortDesc()
                ->values()
                ->all(),
            'monthOptions' => $this->getMonthOptions(),
            'rows' => $this->buildRows($employees, $selectedYear, $selectedMonth),
            'hasTaxationData' => (bool) ($taxationPayload['hasTaxationData'] ?? false),
        ];
    }

    public function buildRows(array $employees, int $year, int $month): array
    {
        return collect($employees)
            ->map(function (array $employee) use ($year, $month) {
                $employeeNo = trim((string) ($employee['employee_no'] ?? ''));

                if ($employeeNo === '') {
                    return null;
                }

                $salaryData = $this->getMonthlySalaryData($employeeNo, $year, $month);
                $amounts = $this->individualTaxAmountService->getAmount($employeeNo, $month, $year);

                return [
                    'employee_no' => $employeeNo,
                    'employee_name' => (string) ($employee['display_name'] ?? $employee['name'] ?? ''),
                    'position' => (string) ($employee['position'] ?? 'N/A') ?: 'N/A',
                    'division_name' => (string) ($employee['division_name'] ?? 'No Division') ?: 'No Division',
                    'unit_name' => (string) ($employee['unit_name'] ?? 'No Unit') ?: 'No Unit',
                    'salary_amount' => $salaryData['salary_amount'],
                    'salary_grade' => $salaryData['salary_grade'],
                    'salary_display' => $salaryData['salary_display'],
                    'salary_tax' => $this->normalizeTaxComponent((array) ($amounts['salary'] ?? [])),
                    'hazard_pay_tax' => $this->normalizeTaxComponent((array) ($amounts['hazard_pay'] ?? [])),
                    'longevity_tax' => $this->normalizeTaxComponent((array) ($amounts['longevity'] ?? [])),
                    'total_tax' => (float) round((float) ($amounts['total'] ?? 0), 2),
                ];
            })
            ->filter()
            ->sortBy('employee_name')
            ->values()
            ->all();
    }

    public function resolveSelectedMonth(?int $month): int
    {
        if ($month !== null && $month >= 1 && $month <= 12) {
            return $month;
        }

        return (int) Carbon::now()->month;
    }

    public function getMonthOptions(): array
    {
        return collect(range(1, 12))
            ->map(fn (int $value) => [
                'value' => $value,
                'label' => Carbon::create()->month($value)->format('F'),
            ])
            ->values()
            ->all();
    }

    private function getMonthlySalaryData(string $employeeNo, int $year, int $month): array
    {
        $monthlyRow = $this->individualTaxDataService
            ->buildMonthlyBreakdown($employeeNo, $year)
            ->firstWhere('month_number', $month);
        $salaryAmount = (float) round((float) data_get($monthlyRow, 'basic_salary', 0), 2);
        $salaryGrade = $this->getSalaryGradeForYear($employeeNo, $year);

        return [
            'salary_amount' => $salaryAmount,
            'salary_grade' => $salaryGrade,
            'salary_display' => $this->formatSalaryDisplay($salaryAmount, $salaryGrade),
        ];
    }

    private function getSalaryGradeForYear(string $employeeNo, int $year): ?string
    {
        $payrollRow = DB::table('payroll_salary_permanent_employees as pspe')
            ->join('payroll_salary as ps', 'ps.id', '=', 'pspe.payroll_salary_id')
            ->where('pspe.employee_no', $employeeNo)
            ->whereRaw('LEFT(ps.payroll_date, 4) = ?', [(string) $year])
            ->orderByDesc('ps.payroll_date')
            ->orderByDesc('pspe.id')
            ->first(['pspe.salary_grade']);

        if ($payrollRow && filled($payrollRow->salary_grade)) {
            return trim((string) $payrollRow->salary_grade);
        }

        $salaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', sprintf('%04d-12-31', $year))
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id')
            ->first(['salary_grade']);

        if (!$salaryRow || !filled($salaryRow->salary_grade)) {
            return null;
        }

        return trim((string) $salaryRow->salary_grade);
    }

    private function formatSalaryDisplay(float $salaryAmount, string|int|null $salaryGrade = null): string
    {
        $salary = 'Php ' . number_format($salaryAmount, 2);

        if ($salaryGrade === null || $salaryGrade === '') {
            return $salary;
        }

        return sprintf('%s (SG-%s)', $salary, trim((string) $salaryGrade));
    }

    private function normalizeTaxComponent(array $component): array
    {
        $status = $this->normalizeStatus((string) ($component['status'] ?? 'forecasted'));

        return [
            'amount' => (float) round((float) ($component['amount'] ?? 0), 2),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'draft' => 'draft',
            'pending' => 'pending',
            'approved' => 'approved',
            'for_releasing' => 'for_releasing',
            'completed', 'actual' => 'completed',
            'override' => 'override',
            default => 'forecasted',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'for_releasing' => 'For Releasing',
            'completed' => 'Completed',
            'override' => 'Override',
            default => 'Forecasted',
        };
    }
}
