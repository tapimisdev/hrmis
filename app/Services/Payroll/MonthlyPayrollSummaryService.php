<?php

namespace App\Services\Payroll;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyPayrollSummaryService
{
    private const PER_PAGE = 15;

    private const PAYABLE_STATUSES = [
        'for_releasing',
        'completed',
    ];

    public function normalizeFilters(array $input): array
    {
        $month = (int) ($input['month'] ?? now()->month);
        $year = (int) ($input['year'] ?? now()->year);

        return [
            'search' => trim((string) ($input['search'] ?? '')),
            'month' => max(1, min(12, $month)),
            'year' => max(2000, $year),
        ];
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = $this->summaryQuery($filters);

        return $query
            ->orderByRaw("LOWER(COALESCE(NULLIF(TRIM(ep.lastname), ''), employees.payroll_name))")
            ->orderByRaw("LOWER(COALESCE(NULLIF(TRIM(ep.firstname), ''), ''))")
            ->orderByRaw("LOWER(COALESCE(NULLIF(TRIM(ep.middlename), ''), ''))")
            ->orderByRaw("LOWER(COALESCE(NULLIF(TRIM(ep.suffix), ''), ''))")
            ->orderBy('employees.employee_no')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function availableYears(): array
    {
        $years = collect()
            ->merge(DB::table('payroll_salary')->selectRaw('YEAR(payroll_date) as year')->pluck('year'))
            ->merge(DB::table('payroll_hazard_pay')->selectRaw('CAST(LEFT(month, 4) AS UNSIGNED) as year')->pluck('year'))
            ->merge(DB::table('payroll_sla_pay')->selectRaw('CAST(LEFT(month, 4) AS UNSIGNED) as year')->pluck('year'))
            ->merge(DB::table('payroll_pera_rata')->selectRaw('CAST(LEFT(month, 4) AS UNSIGNED) as year')->pluck('year'))
            ->merge(DB::table('payroll_longevity_pay')->selectRaw('CAST(LEFT(month, 4) AS UNSIGNED) as year')->pluck('year'))
            ->merge(DB::table('payroll_government_bonus')->selectRaw('CAST(LEFT(month, 4) AS UNSIGNED) as year')->pluck('year'))
            ->filter()
            ->push((int) now()->year)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->all();
    }

    private function summaryQuery(array $filters): Builder
    {
        $nameExpression = "COALESCE(
            NULLIF(
                TRIM(
                    CONCAT(
                        TRIM(COALESCE(ep.lastname, '')),
                        CASE
                            WHEN NULLIF(TRIM(COALESCE(ep.firstname, '')), '') IS NOT NULL
                                THEN CONCAT(', ', TRIM(ep.firstname))
                            ELSE ''
                        END,
                        CASE
                            WHEN NULLIF(TRIM(COALESCE(ep.suffix, '')), '') IS NOT NULL
                                THEN CONCAT(' ', TRIM(ep.suffix))
                            ELSE ''
                        END
                    )
                ),
                ''
            ),
            employees.payroll_name
        )";
        $latestOrg = DB::table('employee_organization as eo1')
            ->where(
                'eo1.id',
                DB::table('employee_organization as eo2')
                    ->select('eo2.id')
                    ->whereColumn('eo2.employee_no', 'eo1.employee_no')
                    ->orderByDesc('eo2.created_at')
                    ->orderByDesc('eo2.id')
                    ->limit(1)
            );

        $query = DB::query()
            ->fromSub($this->employeePoolSubquery($filters), 'employees')
            ->leftJoin('employee_personal as ep', 'employees.employee_no', '=', 'ep.employee_no')
            ->leftJoinSub($latestOrg, 'org', function ($join) {
                $join->on('employees.employee_no', '=', 'org.employee_no');
            })
            ->leftJoin('employment_types as et', 'org.employment_type_id', '=', 'et.id')
            ->leftJoinSub($this->salaryTotalsSubquery($filters), 'salary_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'salary_totals.employee_no');
            })
            ->leftJoinSub($this->hazardTotalsSubquery($filters), 'hazard_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'hazard_totals.employee_no');
            })
            ->leftJoinSub($this->slaTotalsSubquery($filters), 'sla_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'sla_totals.employee_no');
            })
            ->leftJoinSub($this->peraRataTotalsSubquery($filters), 'pera_rata_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'pera_rata_totals.employee_no');
            })
            ->leftJoinSub($this->longevityTotalsSubquery($filters), 'longevity_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'longevity_totals.employee_no');
            })
            ->leftJoinSub($this->governmentBonusTotalsSubquery($filters), 'government_bonus_totals', function ($join) {
                $join->on('employees.employee_no', '=', 'government_bonus_totals.employee_no');
            })
            ->selectRaw('employees.employee_no')
            ->selectRaw("$nameExpression as employee_name")
            ->selectRaw("COALESCE(et.code, et.name, 'N/A') as employment_type")
            ->selectRaw("COALESCE(NULLIF(salary_totals.salary_grade, ''), 'N/A') as salary_grade")
            ->selectRaw('COALESCE(salary_totals.salary_total, 0) as salary_total')
            ->selectRaw('COALESCE(salary_totals.salary_first_cutoff_total, 0) as salary_first_cutoff_total')
            ->selectRaw('COALESCE(salary_totals.salary_second_cutoff_total, 0) as salary_second_cutoff_total')
            ->selectRaw('COALESCE(salary_totals.has_permanent_salary, 0) as has_permanent_salary')
            ->selectRaw('COALESCE(salary_totals.has_cos_salary, 0) as has_cos_salary')
            ->selectRaw('COALESCE(hazard_totals.hazard_pay_total, 0) as hazard_pay_total')
            ->selectRaw('COALESCE(sla_totals.sla_pay_total, 0) as sla_pay_total')
            ->selectRaw('COALESCE(pera_rata_totals.pera_rata_total, 0) as pera_rata_total')
            ->selectRaw('COALESCE(longevity_totals.longevity_total, 0) as longevity_total')
            ->selectRaw('COALESCE(government_bonus_totals.government_bonus_total, 0) as government_bonus_total')
            ->selectRaw('
                COALESCE(salary_totals.salary_total, 0)
                + COALESCE(hazard_totals.hazard_pay_total, 0)
                + COALESCE(sla_totals.sla_pay_total, 0)
                + COALESCE(pera_rata_totals.pera_rata_total, 0)
                + COALESCE(longevity_totals.longevity_total, 0)
                + COALESCE(government_bonus_totals.government_bonus_total, 0)
                as grand_total
            ');

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($innerQuery) use ($search, $nameExpression) {
                $innerQuery->where('employees.employee_no', 'like', $search)
                    ->orWhereRaw("$nameExpression like ?", [$search]);
            });
        }

        return $query;
    }

    private function employeePoolSubquery(array $filters): Builder
    {
        $pool = $this->salaryEmployeeListSubquery($filters)
            ->unionAll($this->hazardEmployeeListSubquery($filters))
            ->unionAll($this->slaEmployeeListSubquery($filters))
            ->unionAll($this->peraRataEmployeeListSubquery($filters))
            ->unionAll($this->longevityEmployeeListSubquery($filters))
            ->unionAll($this->governmentBonusEmployeeListSubquery($filters));

        return DB::query()
            ->fromSub($pool, 'payroll_employees')
            ->selectRaw('employee_no, MAX(payroll_name) as payroll_name')
            ->groupBy('employee_no');
    }

    private function salaryTotalsSubquery(array $filters): Builder
    {
        $records = $this->salaryBaseRecordsSubquery($filters);

        return DB::query()
            ->fromSub($records, 'salary_records')
            ->selectRaw('employee_no, MAX(name) as payroll_name, MAX(salary_grade) as salary_grade, SUM(amount) as salary_total')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN salary_source = 'permanent' THEN ROUND(amount / 2, 2)
                        WHEN cutoff = 'first_cutoff' THEN amount
                        ELSE 0
                    END
                ) as salary_first_cutoff_total
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN salary_source = 'permanent' THEN amount - ROUND(amount / 2, 2)
                        WHEN cutoff = 'second_cutoff' THEN amount
                        ELSE 0
                    END
                ) as salary_second_cutoff_total
            ")
            ->selectRaw("MAX(CASE WHEN salary_source = 'permanent' THEN 1 ELSE 0 END) as has_permanent_salary")
            ->selectRaw("MAX(CASE WHEN salary_source = 'cos' THEN 1 ELSE 0 END) as has_cos_salary")
            ->groupBy('employee_no');
    }

    private function hazardTotalsSubquery(array $filters): Builder
    {
        return DB::table('payroll_hazard_pay as php')
            ->join('payroll_hazard_pay_employee as phpe', 'php.id', '=', 'phpe.payroll_hazard_pay_id')
            ->whereIn('php.status', self::PAYABLE_STATUSES)
            ->where('php.month', $this->periodKey($filters))
            ->selectRaw('phpe.employee_no, MAX(phpe.name) as payroll_name, SUM(phpe.net_pay) as hazard_pay_total')
            ->groupBy('phpe.employee_no');
    }

    private function slaTotalsSubquery(array $filters): Builder
    {
        return DB::table('payroll_sla_pay as psp')
            ->join('payroll_sla_pay_employee as pspe', 'psp.id', '=', 'pspe.payroll_sla_pay_id')
            ->whereIn('psp.status', self::PAYABLE_STATUSES)
            ->where('psp.month', $this->periodKey($filters))
            ->selectRaw('pspe.employee_no, MAX(pspe.name) as payroll_name, SUM(pspe.net_pay) as sla_pay_total')
            ->groupBy('pspe.employee_no');
    }

    private function peraRataTotalsSubquery(array $filters): Builder
    {
        return DB::table('payroll_pera_rata as ppr')
            ->join('payroll_pera_rata_employee as ppre', 'ppr.id', '=', 'ppre.payroll_pera_rata_id')
            ->whereIn('ppr.status', self::PAYABLE_STATUSES)
            ->where('ppr.month', $this->periodKey($filters))
            ->selectRaw('ppre.employee_no, MAX(ppre.name) as payroll_name, SUM(ppre.net_pay) as pera_rata_total')
            ->groupBy('ppre.employee_no');
    }

    private function longevityTotalsSubquery(array $filters): Builder
    {
        return DB::table('payroll_longevity_pay as plp')
            ->join('payroll_longevity_pay_employee as plpe', 'plp.id', '=', 'plpe.payroll_longevity_pay_id')
            ->whereIn('plp.status', self::PAYABLE_STATUSES)
            ->where('plp.month', $this->periodKey($filters))
            ->selectRaw('plpe.employee_no, MAX(plpe.name) as payroll_name, SUM(plpe.net_pay) as longevity_total')
            ->groupBy('plpe.employee_no');
    }

    private function governmentBonusTotalsSubquery(array $filters): Builder
    {
        return DB::table('payroll_government_bonus as pgb')
            ->join('payroll_government_bonus_employee as pgbe', 'pgb.id', '=', 'pgbe.payroll_government_bonus_id')
            ->whereIn('pgb.status', self::PAYABLE_STATUSES)
            ->where('pgb.month', $this->periodKey($filters))
            ->selectRaw('pgbe.employee_no, MAX(pgbe.name) as payroll_name, SUM(pgbe.net_pay) as government_bonus_total')
            ->groupBy('pgbe.employee_no');
    }

    private function salaryEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->salaryTotalsSubquery($filters), 'salary_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function hazardEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->hazardTotalsSubquery($filters), 'hazard_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function slaEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->slaTotalsSubquery($filters), 'sla_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function peraRataEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->peraRataTotalsSubquery($filters), 'pera_rata_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function longevityEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->longevityTotalsSubquery($filters), 'longevity_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function governmentBonusEmployeeListSubquery(array $filters): Builder
    {
        return DB::query()
            ->fromSub($this->governmentBonusTotalsSubquery($filters), 'government_bonus_totals')
            ->selectRaw('employee_no, payroll_name');
    }

    private function salaryBaseRecordsSubquery(array $filters): Builder
    {
        $regular = DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'ps.id', '=', 'pse.payroll_salary_id')
            ->whereIn('ps.status', self::PAYABLE_STATUSES)
            ->whereYear('ps.payroll_date', $filters['year'])
            ->whereMonth('ps.payroll_date', $filters['month'])
            ->selectRaw("'cos' as salary_source, pse.employee_no, pse.name, pse.salary_grade, pse.net_pay as amount, ps.cutoff");

        $permanent = DB::table('payroll_salary as ps')
            ->join('payroll_salary_permanent_employees as pspe', 'ps.id', '=', 'pspe.payroll_salary_id')
            ->whereIn('ps.status', self::PAYABLE_STATUSES)
            ->whereYear('ps.payroll_date', $filters['year'])
            ->whereMonth('ps.payroll_date', $filters['month'])
            ->selectRaw("'permanent' as salary_source, pspe.employee_no, pspe.name, pspe.salary_grade, pspe.net_pay as amount, ps.cutoff");

        return $regular->unionAll($permanent);
    }

    private function periodKey(array $filters): string
    {
        return sprintf('%04d-%02d', $filters['year'], $filters['month']);
    }
}
