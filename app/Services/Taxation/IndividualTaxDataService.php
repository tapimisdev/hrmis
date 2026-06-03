<?php

namespace App\Services\Taxation;

use App\Enums\EmploymentTypesEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndividualTaxDataService
{
    public function getPagePayload(?string $employeeNo = null, ?int $year = null): array
    {
        $employees = $this->getActiveRegularEmployees();
        $employee = $this->resolveEmployee((string) $employeeNo, $employees);

        if (!$employee) {
            abort(404, 'No active regular employee found.');
        }

        $availableYears = $this->getAvailableYears((string) $employee->employee_no);
        $selectedYear = $this->resolveSelectedYear($year, $availableYears);
        $monthlyBreakdown = $this->buildMonthlyBreakdown((string) $employee->employee_no, $selectedYear);
        $otherComponents = $this->getOtherComponents((string) $employee->employee_no, $selectedYear);
        $summary = $this->buildSummary($monthlyBreakdown, $otherComponents);
        $trainLawOptions = $this->getTrainLawOptions();

        return [
            'employee' => $employee,
            'employees' => $employees,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears->values()->all(),
            'monthlyBreakdown' => $monthlyBreakdown->values()->all(),
            'otherComponents' => $otherComponents,
            'summary' => $summary,
            'trainLawOptions' => $trainLawOptions['items'],
            'selectedTrainLawId' => $trainLawOptions['selectedId'],
        ];
    }

    public function getActiveRegularEmployees(): Collection
    {
        $latestOrgDate = DB::table('employee_organization')
            ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
            ->groupBy('employee_no');

        $latestOrgId = DB::table('employee_organization')
            ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
            ->groupBy('employee_no', 'effectivity_date');

        return DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                $join->on('ei.employee_no', '=', 'latest_org_date.employee_no');
            })
            ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                    ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
            })
            ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->where('ei.account_status', 'active')
            ->where('ei.isDeleted', false)
            ->where('eo.employment_type_id', EmploymentTypesEnum::REGULAR->value)
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->orderBy('ei.employee_no')
            ->select(
                'ei.employee_no',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'p.name as position'
            )
            ->get()
            ->map(function ($employee) {
                $employee->display_name = $this->formatEmployeeName($employee);

                return $employee;
            })
            ->values();
    }

    public function resolveEmployee(string $employeeNo, Collection $employees): ?object
    {
        if ($employeeNo !== '') {
            $selectedEmployee = $employees->firstWhere('employee_no', $employeeNo);

            if ($selectedEmployee) {
                return $selectedEmployee;
            }
        }

        return $employees->sortBy('employee_no')->first();
    }

    public function getAvailableYears(string $employeeNo): Collection
    {
        $salaryYears = DB::table('payroll_salary_permanent_employees as pspe')
            ->join('payroll_salary as ps', 'ps.id', '=', 'pspe.payroll_salary_id')
            ->where('pspe.employee_no', $employeeNo)
            ->selectRaw('DISTINCT YEAR(ps.payroll_date) as year')
            ->pluck('year');

        $componentYears = DB::table('employee_payroll_components as epc')
            ->join('payroll_components_years as pcy', 'pcy.id', '=', 'epc.tax_deduction_id')
            ->where('epc.employee_no', $employeeNo)
            ->selectRaw('DISTINCT pcy.year as year')
            ->pluck('year');

        return $salaryYears
            ->merge($componentYears)
            ->filter()
            ->map(fn ($itemYear) => (int) $itemYear)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function resolveSelectedYear(?int $requestedYear, Collection $availableYears): int
    {
        if ($requestedYear && $requestedYear >= 1000 && $requestedYear <= 9999) {
            return $requestedYear;
        }

        return (int) ($availableYears->first() ?? Carbon::now()->year);
    }

    public function buildMonthlyBreakdown(string $employeeNo, int $year): Collection
    {
        $salaryRows = DB::table('payroll_salary_permanent_employees as pspe')
            ->join('payroll_salary as ps', 'ps.id', '=', 'pspe.payroll_salary_id')
            ->where('pspe.employee_no', $employeeNo)
            ->whereYear('ps.payroll_date', $year)
            ->orderByDesc('ps.payroll_date')
            ->get([
                'ps.payroll_date',
                'pspe.monthly_rate',
                'pspe.overtime',
            ])
            ->groupBy(fn ($row) => (int) Carbon::parse($row->payroll_date)->month)
            ->map(fn ($rows) => $rows->first());

        $hazardRows = DB::table('payroll_hazard_pay_employee as phe')
            ->join('payroll_hazard_pay as php', 'php.id', '=', 'phe.payroll_hazard_pay_id')
            ->where('phe.employee_no', $employeeNo)
            ->whereRaw('LEFT(php.month, 4) = ?', [(string) $year])
            ->orderByDesc('php.month')
            ->get([
                'php.month',
                'phe.hazard_pay',
            ])
            ->groupBy(fn ($row) => (int) substr((string) $row->month, 5, 2))
            ->map(fn ($rows) => $rows->first());

        $longevityRows = DB::table('payroll_longevity_pay_employee as plpe')
            ->join('payroll_longevity_pay as plp', 'plp.id', '=', 'plpe.payroll_longevity_pay_id')
            ->where('plpe.employee_no', $employeeNo)
            ->whereRaw('LEFT(plp.month, 4) = ?', [(string) $year])
            ->orderByDesc('plp.month')
            ->get([
                'plp.month',
                'plpe.longevity_amount',
            ])
            ->groupBy(fn ($row) => (int) substr((string) $row->month, 5, 2))
            ->map(fn ($rows) => $rows->first());

        $taxRows = DB::table('employee_payroll_components as epc')
            ->join('payroll_components_years as pcy', 'pcy.id', '=', 'epc.tax_deduction_id')
            ->join('payroll_components as pc', 'pc.id', '=', 'pcy.payroll_component_id')
            ->where('epc.employee_no', $employeeNo)
            ->where('pcy.year', $year)
            ->where('pc.type', 'taxes')
            ->get([
                'epc.month',
                'pc.name',
                'epc.amount',
            ])
            ->groupBy(fn ($row) => (int) $row->month);

        return collect(range(1, 12))->map(function (int $month) use ($salaryRows, $hazardRows, $longevityRows, $taxRows) {
            $salary = $salaryRows->get($month);
            $hazard = $hazardRows->get($month);
            $longevity = $longevityRows->get($month);
            $taxForMonth = collect($taxRows->get($month, []))->sum('amount');

            $basicSalary = (float) ($salary->monthly_rate ?? 0);
            $hazardPay = (float) ($hazard->hazard_pay ?? 0);
            $longevityPay = (float) ($longevity->longevity_amount ?? 0);
            $total = $basicSalary + $hazardPay + $longevityPay;

            return [
                'month_number' => $month,
                'month_label' => Carbon::create()->month($month)->format('F'),
                'basic_salary' => $basicSalary,
                'hazard_pay' => $hazardPay,
                'longevity_pay' => $longevityPay,
                'total' => $total,
                'tax_withheld' => (float) $taxForMonth,
            ];
        });
    }

    public function getOtherComponents(string $employeeNo, int $year): array
    {
        $otherEarningTaxTypes = $this->getOtherEarningTaxTypesByYear($year);

        $rows = DB::table('employee_payroll_components as epc')
            ->join('payroll_components_years as pcy', 'pcy.id', '=', 'epc.tax_deduction_id')
            ->join('payroll_components as pc', 'pc.id', '=', 'pcy.payroll_component_id')
            ->where('epc.employee_no', $employeeNo)
            ->where('pcy.year', $year)
            ->get([
                'pc.name',
                'pc.type',
                'epc.amount',
            ]);

        $normalizedEarnings = $rows
            ->where('type', 'earnings')
            ->reject(fn ($row) => in_array(strtolower((string) $row->name), [
                'hazard pay',
                'longetivity pay',
                'longevity pay',
            ], true))
            ->groupBy('name')
            ->map(function ($group, $name) use ($otherEarningTaxTypes) {
                $normalizedName = strtolower(trim((string) $name));

                return [
                    'name' => $name,
                    'tax_type' => $otherEarningTaxTypes[$normalizedName] ?? 'taxable',
                    'amount' => (float) collect($group)->sum('amount'),
                ];
            })
            ->sortBy('name')
            ->values();

        $earnings = $normalizedEarnings
            ->filter(fn ($item) => ($item['tax_type'] ?? 'taxable') === 'taxable')
            ->values()
            ->all();

        $deMinimis = $normalizedEarnings
            ->filter(fn ($item) => in_array(($item['tax_type'] ?? ''), ['non_taxable', 'exempt'], true))
            ->values()
            ->all();

        $taxes = $rows
            ->where('type', 'taxes')
            ->groupBy('name')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'amount' => (float) collect($group)->sum('amount'),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return [
            'earnings' => $earnings,
            'de_minimis' => $deMinimis,
            'taxes' => $taxes,
        ];
    }

    public function buildSummary(Collection $monthlyBreakdown, array $otherComponents): array
    {
        $annualBasicSalary = (float) $monthlyBreakdown->sum('basic_salary');
        $annualHazardPay = (float) $monthlyBreakdown->sum('hazard_pay');
        $annualLongevityPay = (float) $monthlyBreakdown->sum('longevity_pay');
        $grossTaxableIncome = (float) $monthlyBreakdown->sum('total');
        $otherEarnings = (float) collect($otherComponents['earnings'] ?? [])->sum('amount');
        $deMinimisTotal = (float) collect($otherComponents['de_minimis'] ?? [])->sum('amount');
        $taxWithheld = (float) $monthlyBreakdown->sum('tax_withheld');
        $netAfterTax = ($grossTaxableIncome + $otherEarnings + $deMinimisTotal) - $taxWithheld;

        return [
            'annual_basic_salary' => $annualBasicSalary,
            'annual_hazard_pay' => $annualHazardPay,
            'annual_longevity_pay' => $annualLongevityPay,
            'gross_taxable_income' => $grossTaxableIncome,
            'other_earnings' => $otherEarnings,
            'de_minimis_total' => $deMinimisTotal,
            'total_tax_withheld' => $taxWithheld,
            'net_after_tax' => $netAfterTax,
        ];
    }

    private function getOtherEarningTaxTypesByYear(int $year): array
    {
        $taxationId = DB::table('taxations')
            ->where('is_active', true)
            ->where('year', $year)
            ->value('id');

        if (!$taxationId) {
            return [];
        }

        return DB::table('taxation_other_earnings')
            ->where('taxation_id', $taxationId)
            ->get(['name', 'tax_type'])
            ->mapWithKeys(fn ($item) => [
                strtolower(trim((string) $item->name)) => strtolower((string) ($item->tax_type ?? 'taxable')),
            ])
            ->all();
    }

    private function getTrainLawOptions(): array
    {
        $items = DB::table('train_law')
            ->where('is_active', true)
            ->orderByDesc('year')
            ->get(['id', 'year'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'year' => (string) $item->year,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'selectedId' => $items[0]['id'] ?? null,
        ];
    }

    public function formatEmployeeName(object $employee): string
    {
        return collect([
            $employee->lastname ?? null,
            isset($employee->firstname) ? ', ' . $employee->firstname : null,
            !empty($employee->middlename) ? ' ' . strtoupper(substr($employee->middlename, 0, 1)) . '.' : null,
            !empty($employee->suffix) ? ' ' . $employee->suffix : null,
        ])->implode('');
    }
}
