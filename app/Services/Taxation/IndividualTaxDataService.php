<?php

namespace App\Services\Taxation;

use App\Enums\EmploymentTypesEnum;
use App\Enums\TableSettingsEnum;
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
        $individualTaxSettings = $this->getIndividualTaxSettings($year);

        $salaryRows = DB::table('payroll_salary_permanent_employees as pspe')
            ->join('payroll_salary as ps', 'ps.id', '=', 'pspe.payroll_salary_id')
            ->where('pspe.employee_no', $employeeNo)
            ->whereYear('ps.payroll_date', $year)
            ->orderByDesc('ps.payroll_date')
            ->get([
                'ps.payroll_date',
                'ps.status',
                'pspe.monthly_rate',
                'pspe.overtime',
            ])
            ->groupBy(fn ($row) => (int) Carbon::parse($row->payroll_date)->month)
            ->map(fn ($rows) => $rows->first());

        $forecastSalaryByMonth = $this->getForecastSalaryByMonth($employeeNo, $year);

        $hazardRows = DB::table('payroll_hazard_pay_employee as phe')
            ->join('payroll_hazard_pay as php', 'php.id', '=', 'phe.payroll_hazard_pay_id')
            ->where('phe.employee_no', $employeeNo)
            ->whereRaw('LEFT(php.month, 4) = ?', [(string) $year])
            ->orderByDesc('php.month')
            ->get([
                'php.month',
                'php.status',
                'phe.hazard_pay',
            ])
            ->groupBy(fn ($row) => (int) substr((string) $row->month, 5, 2))
            ->map(fn ($rows) => $rows->first());

        $forecastHazardByMonth = $this->getForecastHazardByMonth(
            $forecastSalaryByMonth,
            (bool) ($individualTaxSettings['is_hazard_pay'] ?? false) || $hazardRows->isNotEmpty()
        );

        $longevityRows = DB::table('payroll_longevity_pay_employee as plpe')
            ->join('payroll_longevity_pay as plp', 'plp.id', '=', 'plpe.payroll_longevity_pay_id')
            ->where('plpe.employee_no', $employeeNo)
            ->whereRaw('LEFT(plp.month, 4) = ?', [(string) $year])
            ->orderByDesc('plp.month')
            ->get([
                'plp.month',
                'plp.status',
                'plpe.longevity_amount',
            ])
            ->groupBy(fn ($row) => (int) substr((string) $row->month, 5, 2))
            ->map(fn ($rows) => $rows->first());

        $forecastLongevityByMonth = $this->getForecastComponentByMonth(
            TableSettingsEnum::LONGETIVITY->value,
            $employeeNo,
            $year,
            (bool) ($individualTaxSettings['is_longevity'] ?? false) || $longevityRows->isNotEmpty()
        );

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

        $lastActualHazardMonth = $this->getLastActualMonth($hazardRows->keys()->all());

        return collect(range(1, 12))->map(function (int $month) use (
            $salaryRows,
            $hazardRows,
            $longevityRows,
            $taxRows,
            $forecastSalaryByMonth,
            $forecastHazardByMonth,
            $forecastLongevityByMonth,
            $lastActualHazardMonth
        ) {
            $salary = $salaryRows->get($month);
            $hazard = $hazardRows->get($month);
            $longevity = $longevityRows->get($month);
            $taxForMonth = collect($taxRows->get($month, []))->sum('amount');

            $salarySource = $salary ? (string) ($salary->status ?? 'completed') : 'forecast';
            $basicSalary = $salary
                ? (float) $salary->monthly_rate
                : (float) ($forecastSalaryByMonth[$month] ?? 0);

            if ($hazard) {
                $hazardPay = (float) $hazard->hazard_pay;
                $hazardSource = (string) ($hazard->status ?? 'completed');
            } else {
                [$hazardPay, $hazardSource] = $this->resolveMonthlyComponentAmount(
                    $month,
                    null,
                    (float) ($forecastHazardByMonth[$month] ?? 0),
                    $lastActualHazardMonth
                );
            }

            $longevitySource = $longevity ? (string) ($longevity->status ?? 'completed') : 'forecast';
            $longevityPay = $longevity
                ? (float) $longevity->longevity_amount
                : (float) ($forecastLongevityByMonth[$month] ?? 0);
            $total = $basicSalary + $hazardPay + $longevityPay;
            $rowStatuses = collect([$salarySource, $hazardSource, $longevitySource]);
            $rowSource = $rowStatuses->contains(fn ($status) => $status !== 'forecast')
                ? $rowStatuses
                    ->reject(fn ($status) => $status === 'forecast')
                    ->sortBy(fn ($status) => $this->payrollStatusPriority($status))
                    ->first()
                : 'forecast';

            return [
                'month_number' => $month,
                'month_label' => Carbon::create()->month($month)->format('F'),
                'basic_salary' => $basicSalary,
                'hazard_pay' => $hazardPay,
                'longevity_pay' => $longevityPay,
                'total' => $total,
                'tax_withheld' => (float) $taxForMonth,
                'source' => $rowSource,
                'source_label' => $this->formatPayrollStatusLabel($rowSource),
                'source_breakdown' => [
                    'basic_salary' => $salarySource,
                    'hazard_pay' => $hazardSource,
                    'longevity_pay' => $longevitySource,
                ],
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
            ->values()
            ->all();

        $deMinimis = [];

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

        $governmentBonuses = $this->getGovernmentBonuses($employeeNo, $year);
        $allowables = $this->getAllowablesFromModule($employeeNo, $year);

        return [
            'earnings' => $earnings,
            'de_minimis' => $deMinimis,
            'government_bonuses' => $governmentBonuses,
            'allowables' => $allowables,
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
        $governmentBonusesTotal = (float) collect($otherComponents['government_bonuses'] ?? [])->sum('amount');
        $allowablesTotal = (float) collect($otherComponents['allowables'] ?? [])->sum('amount');
        $taxWithheld = (float) $monthlyBreakdown->sum('tax_withheld');
        $netAfterTax = ($grossTaxableIncome + $otherEarnings + $governmentBonusesTotal) - $taxWithheld;

        return [
            'annual_basic_salary' => $annualBasicSalary,
            'annual_hazard_pay' => $annualHazardPay,
            'annual_longevity_pay' => $annualLongevityPay,
            'gross_taxable_income' => $grossTaxableIncome,
            'other_earnings' => $otherEarnings,
            'de_minimis_total' => $deMinimisTotal,
            'government_bonuses_total' => $governmentBonusesTotal,
            'allowables_total' => $allowablesTotal,
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

    private function getIndividualTaxSettings(int $year): array
    {
        $settings = DB::table('n_taxation as nt')
            ->join('n_taxation_settings as nts', 'nts.n_taxation_id', '=', 'nt.UniqueID')
            ->where('nt.Year', $year)
            ->select([
                'nts.is_hazard_pay',
                'nts.is_longevity',
                'nts.is_less_bir',
                'nts.train_law_id',
            ])
            ->first();

        return [
            'is_hazard_pay' => (bool) ($settings->is_hazard_pay ?? false),
            'is_longevity' => (bool) ($settings->is_longevity ?? false),
            'is_less_bir' => (bool) ($settings->is_less_bir ?? false),
            'train_law_id' => $settings->train_law_id ?? null,
        ];
    }

    private function getForecastSalaryByMonth(string $employeeNo, int $year): array
    {
        $rates = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', Carbon::create($year, 12, 31)->toDateString())
            ->orderBy('effectivity_date')
            ->get(['effectivity_date', 'amount'])
            ->map(fn ($row) => [
                'effectivity_date' => Carbon::parse($row->effectivity_date)->startOfDay(),
                'amount' => (float) str_replace(',', '', (string) ($row->amount ?? 0)),
            ])
            ->values();

        $forecast = [];
        $latestAmount = 0.0;
        $rateIndex = 0;

        foreach (range(1, 12) as $month) {
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

            while ($rateIndex < $rates->count() && $rates[$rateIndex]['effectivity_date']->lte($monthEnd)) {
                $latestAmount = (float) $rates[$rateIndex]['amount'];
                $rateIndex++;
            }

            $forecast[$month] = (float) round($latestAmount, 4);
        }

        return $forecast;
    }

    private function getForecastHazardByMonth(array $forecastSalaryByMonth, bool $isEnabled): array
    {
        $forecast = [];

        foreach (range(1, 12) as $month) {
            $forecast[$month] = $isEnabled
                ? (float) round(((float) ($forecastSalaryByMonth[$month] ?? 0)) * 0.15, 4)
                : 0.0;
        }

        return $forecast;
    }

    private function getForecastComponentByMonth(
        string $type,
        string $employeeNo,
        int $year,
        bool $isEnabled
    ): array {
        $forecast = array_fill_keys(range(1, 12), 0.0);

        if (!$isEnabled) {
            return $forecast;
        }

        $componentId = DB::table('payroll_components_settings')
            ->where('type', $type)
            ->value('table_id');

        if (!$componentId) {
            return $forecast;
        }

        $componentYearId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentId)
            ->where('year', $year)
            ->value('id');

        if (!$componentYearId) {
            return $forecast;
        }

        return DB::table('employee_payroll_components')
            ->selectRaw('month, SUM(amount) as total')
            ->where('tax_deduction_id', $componentYearId)
            ->where('employee_no', $employeeNo)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(fn ($amount, $month) => [
                (int) $month => (float) round((float) $amount, 4),
            ])
            ->union(collect($forecast))
            ->sortKeys()
            ->all();
    }

    private function getLastActualMonth(array $actualMonths): ?int
    {
        $month = collect($actualMonths)
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value >= 1 && $value <= 12)
            ->max();

        return $month ? (int) $month : null;
    }

    private function resolveMonthlyComponentAmount(
        int $month,
        ?float $actualAmount,
        float $forecastAmount,
        ?int $lastActualMonth
    ): array {
        if ($actualAmount !== null) {
            return [(float) $actualAmount, null];
        }

        if ($lastActualMonth !== null && $month <= $lastActualMonth) {
            return [0.0, 'forecast'];
        }

        return [(float) $forecastAmount, 'forecast'];
    }

    private function payrollStatusPriority(string $status): int
    {
        return match ($status) {
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'for_releasing' => 4,
            'completed' => 5,
            default => 99,
        };
    }

    private function formatPayrollStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'for_releasing' => 'For Releasing',
            'completed' => 'Completed',
            default => 'Forecasted',
        };
    }

    private function getAllowablesFromModule(string $employeeNo, int $year): array
    {
        $moduleIds = [
            TableSettingsEnum::GSIS->value,
            TableSettingsEnum::PAGIBIG->value,
            TableSettingsEnum::PHILHEALTH->value,
        ];

        $rows = DB::table('module_tab_employees')
            ->select('module_tab_id', 'month', 'amount')
            ->where('employee_no', $employeeNo)
            ->where('year', $year)
            ->whereIn('module_tab_id', $moduleIds)
            ->get();

        $grouped = $rows->groupBy('module_tab_id');

        $gsis = (float) round(
            (float) collect($grouped->get(TableSettingsEnum::GSIS->value, collect()))->sum('amount'),
            4
        );

        $philhealth = (float) round(
            (float) collect($grouped->get(TableSettingsEnum::PHILHEALTH->value, collect()))->sum('amount'),
            4
        );

        $pagibig = (float) round(
            collect($grouped->get(TableSettingsEnum::PAGIBIG->value, collect()))
                ->groupBy('month')
                ->sum(fn ($items) => min((float) $items->sum('amount'), 200.00)),
            4
        );

        return collect([
            ['name' => 'GSIS', 'amount' => $gsis],
            ['name' => 'PhilHealth', 'amount' => $philhealth],
            ['name' => 'Pag-IBIG', 'amount' => $pagibig],
        ])
            ->filter(fn ($item) => (float) ($item['amount'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function getGovernmentBonuses(string $employeeNo, int $year): array
    {
        $actualBonuses = DB::table('payroll_government_bonus_employee as pgbe')
            ->join('payroll_government_bonus as pgb', 'pgbe.payroll_government_bonus_id', '=', 'pgb.id')
            ->join('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
            ->where('pgbe.employee_no', $employeeNo)
            ->where('pgb.status', 'completed')
            ->whereRaw('YEAR(CONCAT(pgb.month, "-01")) = ?', [$year])
            ->orderBy('pgb.month')
            ->get([
                'pgb.month',
                'gbt.name',
                'pgbe.bonus_amount',
            ])
            ->map(fn ($row) => [
                'name' => trim((string) ($row->name ?? 'Government Bonus')),
                'month' => (string) ($row->month ?? ''),
                'amount' => (float) round((float) ($row->bonus_amount ?? 0), 4),
                'source' => 'payroll_actual',
            ])
            ->values()
            ->all();

        if (!empty($actualBonuses)) {
            return $actualBonuses;
        }

        return $this->getForecastGovernmentBonuses($employeeNo, $year);
    }

    private function getForecastGovernmentBonuses(string $employeeNo, int $year): array
    {
        $bonusTypes = DB::table('government_bonus_types as gbt')
            ->where('gbt.is_active', true)
            ->orderBy('gbt.name')
            ->get([
                'gbt.id',
                'gbt.name',
                'gbt.computation_type',
                'gbt.computation_value',
                'gbt.formula_expression',
                'gbt.service_date_basis',
                'gbt.require_active_account',
                'gbt.require_work_shift',
                'gbt.require_information',
                'gbt.require_salary',
                'gbt.min_years_of_service',
                'gbt.min_months_of_service',
            ]);

        if ($bonusTypes->isEmpty()) {
            return [];
        }

        $employee = $this->getGovernmentBonusEmployeeContext($employeeNo);
        $payoutMonth = Carbon::create($year, 12, 1)->format('Y-m');

        return $bonusTypes
            ->map(function ($bonusType) use ($employee, $employeeNo, $payoutMonth) {
                if (!$this->employeeMeetsGovernmentBonusEligibility($employee, $employeeNo, $bonusType, $payoutMonth)) {
                    return null;
                }

                $amount = $this->computeForecastGovernmentBonusAmount($employeeNo, $bonusType, $payoutMonth);

                if ($amount <= 0) {
                    return null;
                }

                return [
                    'name' => trim((string) ($bonusType->name ?? 'Government Bonus')),
                    'month' => $payoutMonth,
                    'amount' => (float) round($amount, 4),
                    'source' => 'forecast',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function getGovernmentBonusEmployeeContext(string $employeeNo): ?object
    {
        return DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('users as u', 'ei.user_id', '=', 'u.id')
            ->where('ei.employee_no', $employeeNo)
            ->select(
                'ei.employee_no',
                'ei.account_status',
                'ei.user_id',
                'ei.date_hired_company',
                'ei.date_hired_organization',
                'ep.id as employee_personal_id',
                'p.id as position_id'
            )
            ->orderByDesc('eo.created_at')
            ->orderByDesc('eo.id')
            ->first();
    }

    private function employeeMeetsGovernmentBonusEligibility(
        ?object $employee,
        string $employeeNo,
        object $bonusType,
        string $payoutMonth
    ): bool {
        if (!$employee) {
            return false;
        }

        if ((bool) ($bonusType->require_active_account ?? false) && ($employee->account_status ?? null) !== 'active') {
            return false;
        }

        if ((bool) ($bonusType->require_information ?? false) && !$this->hasGovernmentBonusInformation($employee)) {
            return false;
        }

        if ((bool) ($bonusType->require_salary ?? false) && !$this->hasGovernmentBonusSalary($employeeNo, $payoutMonth)) {
            return false;
        }

        if ((bool) ($bonusType->require_work_shift ?? false) && !$this->hasGovernmentBonusShift($employeeNo, $payoutMonth)) {
            return false;
        }

        return $this->meetsGovernmentBonusMinimumService($employee, $bonusType, $payoutMonth);
    }

    private function hasGovernmentBonusInformation(object $employee): bool
    {
        return !empty($employee->user_id)
            && !empty($employee->employee_personal_id)
            && !empty($employee->position_id);
    }

    private function hasGovernmentBonusSalary(string $employeeNo, string $payoutMonth): bool
    {
        return $this->getSalaryAsOfMonth($employeeNo, $payoutMonth) > 0;
    }

    private function hasGovernmentBonusShift(string $employeeNo, string $payoutMonth): bool
    {
        $endDate = Carbon::parse($payoutMonth . '-01')->endOfMonth()->toDateString();

        return DB::table('employee_shift_work_schedule as sw1')
            ->where(
                'sw1.id',
                DB::table('employee_shift_work_schedule as sw2')
                    ->select('sw2.id')
                    ->whereColumn('sw2.employee_no', 'sw1.employee_no')
                    ->where('sw2.employee_no', $employeeNo)
                    ->where('sw2.created_at', '<=', $endDate)
                    ->orderByDesc('sw2.created_at')
                    ->orderByDesc('sw2.id')
                    ->limit(1)
            )
            ->exists();
    }

    private function meetsGovernmentBonusMinimumService(
        object $employee,
        object $bonusType,
        string $payoutMonth
    ): bool {
        $minYears = $bonusType->min_years_of_service;
        $minMonths = $bonusType->min_months_of_service;

        if (is_null($minYears) && is_null($minMonths)) {
            return true;
        }

        $periodEnd = Carbon::parse($payoutMonth . '-01')->endOfMonth();
        $serviceDate = match ((string) ($bonusType->service_date_basis ?? 'organization')) {
            'company' => $employee->date_hired_company,
            'current_year' => Carbon::create($periodEnd->year, 1, 1)->toDateString(),
            default => $employee->date_hired_organization,
        };

        if (!$serviceDate) {
            return false;
        }

        $serviceMonths = Carbon::parse($serviceDate)->diffInMonths($periodEnd);
        $requiredMonths = (int) (($minYears ?? 0) * 12) + (int) ($minMonths ?? 0);

        return $serviceMonths >= $requiredMonths;
    }

    private function computeForecastGovernmentBonusAmount(
        string $employeeNo,
        object $bonusType,
        string $payoutMonth
    ): float {
        $salary = $this->getSalaryAsOfMonth($employeeNo, $payoutMonth);
        $computationType = (string) ($bonusType->computation_type ?? 'manual');

        if ($computationType === 'fixed') {
            return (float) ($bonusType->computation_value ?? 0);
        }

        if ($computationType === 'percentage') {
            return $salary * (((float) ($bonusType->computation_value ?? 0)) / 100);
        }

        if ($computationType === 'formula') {
            return $this->evaluateGovernmentBonusFormula(
                (string) ($bonusType->formula_expression ?? ''),
                [
                    'salary' => $salary,
                    'basic_salary' => $salary,
                    'monthly_salary' => $salary,
                    'years_of_service' => $this->getGovernmentBonusYearsOfService($bonusType, $payoutMonth, $employeeNo),
                    'months_of_service' => $this->getGovernmentBonusMonthsOfService($bonusType, $payoutMonth, $employeeNo),
                ]
            );
        }

        return 0.0;
    }

    private function getSalaryAsOfMonth(string $employeeNo, string $payoutMonth): float
    {
        $endDate = Carbon::parse($payoutMonth . '-01')->endOfMonth()->toDateString();

        $salaryRow = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $endDate)
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id')
            ->first(['amount']);

        return (float) str_replace(',', '', (string) ($salaryRow->amount ?? 0));
    }

    private function getGovernmentBonusYearsOfService(object $bonusType, string $payoutMonth, string $employeeNo): float
    {
        $serviceDate = $this->getGovernmentBonusServiceDate($bonusType, $payoutMonth, $employeeNo);

        if (!$serviceDate) {
            return 0.0;
        }

        return (float) Carbon::parse($serviceDate)
            ->diffInYears(Carbon::parse($payoutMonth . '-01')->endOfMonth());
    }

    private function getGovernmentBonusMonthsOfService(object $bonusType, string $payoutMonth, string $employeeNo): float
    {
        $serviceDate = $this->getGovernmentBonusServiceDate($bonusType, $payoutMonth, $employeeNo);

        if (!$serviceDate) {
            return 0.0;
        }

        return (float) Carbon::parse($serviceDate)
            ->diffInMonths(Carbon::parse($payoutMonth . '-01')->endOfMonth());
    }

    private function getGovernmentBonusServiceDate(object $bonusType, string $payoutMonth, string $employeeNo): ?string
    {
        if (($bonusType->service_date_basis ?? 'organization') === 'current_year') {
            return Carbon::parse($payoutMonth . '-01')->startOfYear()->toDateString();
        }

        $employee = $this->getGovernmentBonusEmployeeContext($employeeNo);

        if (!$employee) {
            return null;
        }

        return ($bonusType->service_date_basis ?? 'organization') === 'company'
            ? ($employee->date_hired_company ?? null)
            : ($employee->date_hired_organization ?? null);
    }

    private function evaluateGovernmentBonusFormula(string $formula, array $variables): float
    {
        $expression = strtolower(trim($formula));

        if ($expression === '') {
            return 0.0;
        }

        uksort($variables, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($variables as $name => $value) {
            $expression = preg_replace(
                '/\b' . preg_quote(strtolower($name), '/') . '\b/',
                (string) round((float) $value, 8),
                $expression
            );
        }

        if (preg_match('/[a-z_]/', $expression)) {
            return 0.0;
        }

        if (!preg_match('/^[0-9+\-*\/().\s<>=!?:&|]+$/', $expression)) {
            return 0.0;
        }

        set_error_handler(static function () {
            throw new \ErrorException('Invalid formula.');
        });

        try {
            $result = eval('return ' . $expression . ';');
        } catch (\Throwable) {
            $result = 0;
        } finally {
            restore_error_handler();
        }

        return is_numeric($result) ? round((float) $result, 2) : 0.0;
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
