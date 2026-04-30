<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetEmployeeService {
    
    protected $payroll_id;
    protected $isGrouped;

    public $employees;

    public function __construct($payroll_id, $isGrouped)
    {
        $this->payroll_id = $payroll_id;
        $this->isGrouped = $isGrouped;
    }
    
    public function getAndMapEmployeeSalary()
    {
        $payroll = DB::table('payroll_salary')
            ->where('id', $this->payroll_id)
            ->select('payroll_date', 'employment_type_id')
            ->first();

        $payroll_date = $payroll->payroll_date;
        $employment_type_id = $payroll->employment_type_id;

        // COS
        if($employment_type_id == EmploymentTypesEnum::COS->value) {
            $pse = DB::table('payroll_salary_employee as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->where('payroll_salary_id', $this->payroll_id)
                ->select('pse.*', 'ps.payroll_no', 'ps.payroll_date', 'ps.cutoff', 'ps.period_covered', 'ps.apply_deduction', 'ps.deduction_apply_options')
                ->get();

            // Get all projects for this payroll date
            $projects = DB::table('employee_projects as ep')
                ->join('projects', 'ep.project_id', '=', 'projects.id')
                ->whereDate('start_date', '<=', $payroll_date)
                ->where(function ($query) use ($payroll_date) {
                    $query->whereDate('end_date', '>=', $payroll_date)
                        ->orWhereNull('end_date');
                })
                ->select('projects.id', 'projects.name')
                ->get()->unique('id');

            $enriched = $pse->map(function ($d) use ($payroll_date) {
                $deductions = DB::table('payroll_salary_employee_edeductions')
                    ->where('payroll_se_id', $d->id)
                    ->get();

                $earnings = DB::table('payroll_salary_employee_earnings')
                    ->where('payroll_se_id', $d->id)
                    ->get();

                $project_id = DB::table('employee_projects')
                    ->where('employee_no', $d->employee_no)
                    ->whereDate('start_date', '<=', $payroll_date)
                    ->where(function ($query) use ($payroll_date) {
                        $query->whereDate('end_date', '>=', $payroll_date)
                            ->orWhereNull('end_date');
                    })
                    ->orderByDesc('start_date')
                    ->value('project_id');

                return (object) [
                    'id' => $d->id,
                    'employee_no' => $d->employee_no,
                    'name' => strtoupper($d->name),
                    'position' => ucfirst($d->position),
                    'monthly_rate' => $d->monthly_rate,
                    'salary_grade' => $d->salary_grade,
                    'ut' => $d->ut,
                    'absences' => $d->absences,
                    'overtime' => $d->overtime,
                    'holiday' => $d->holiday,
                    'gsis' => $d->gsis,
                    'philhealth' => $d->philhealth,
                    'pagibig' => $d->pagibig,
                    'w_tax' => $d->w_tax,
                    'total_earnings' => $d->total_earnings,
                    'ewt_2' => $d->ewt_2,
                    'percentage_tax_3' => $d->percentage_tax_3,
                    'tax_ewt_5' => $d->tax_ewt_5,
                    'hmo' => $d->hmo,
                    'basic_pay' => $d->basic_pay,
                    'gross_pay' => $d->gross_pay,
                    'net_pay' => $d->net_pay,
                    'salary_adjustment' => $d->salary_adjustment,
                    'deductions' => $deductions ?? [],
                    'earnings' => $earnings ?? [],
                    'project_id' => $project_id,
                    'remarks' => $d->remarks ?? null,
                    'payroll_no' => $d->payroll_no,
                    'payroll_date' => $d->payroll_date,
                    'cutoff' => $d->cutoff,
                    'period_covered' => $d->period_covered,
                    'apply_deduction' => (bool) ($d->apply_deduction ?? true),
                    'deduction_apply_options' => $d->deduction_apply_options ?? null,
                ];
            });

            if ($this->isGrouped) {
                // Group employees by project
                $this->employees = [];

                foreach ($enriched as $employee) {
                    $emp_project = $projects->firstWhere('id', $employee->project_id);

                    $projectId = $emp_project->id ?? 'others';
                    $projectName = $emp_project->name ?? 'No Projects';

                    if (!isset($this->employees[$projectId])) {
                        $this->employees[$projectId] = [
                            'name' => $projectName,
                            'employees' => []
                        ];
                    }

                    $autBreakdown = $this->getAutBreakdown($employee);
                    $aut = collect($autBreakdown)->sum('amount');
                    $deductionBreakdowns = $this->getDeductionBreakdowns($employee);
                    $deductionBreakdowns['aut'] = $autBreakdown;
                    $deferredDeductionReference = $this->getDeferredCurrentDeductionReference($employee);

                    $this->employees[$projectId]['employees'][] = [
                        'id' => $employee->id,
                        'employee_no' => $employee->employee_no,
                        'name' => $employee->name,
                        'position' => $employee->position,
                        'monthly_rate' => $employee->monthly_rate,
                        'salary_earned' => $employee->basic_pay,
                        'aut' => $aut,
                        'aut_breakdown' => $autBreakdown,
                        'ut' => $employee->ut,
                        'absences' => $employee->absences,
                        'overtime' => $employee->overtime,
                        'holiday' => $employee->holiday,
                        'total_salary' => $employee->gross_pay - $aut,
                        'ewt_2' => $employee->ewt_2,
                        'percentage_tax_3' => $employee->percentage_tax_3,
                        'tax_ewt_5' => $employee->tax_ewt_5,
                        'w_tax' => $employee->w_tax,
                        'hmo' => $employee->hmo,
                        'deferred_deduction_reference' => $deferredDeductionReference,
                        'deferred_deduction_reference_total' => collect($deferredDeductionReference)->sum('amount'),
                        'deduction_breakdowns' => $deductionBreakdowns,
                        'deductions' => $employee->deductions,
                        'earnings' => $employee->earnings,
                        'adjustment' => $employee->salary_adjustment,
                        'net_salary' => $employee->net_pay,
                        'remarks' => $employee->remarks ?? '',
                    ];
                }

                $this->employees = array_values($this->employees);

            } else {
                // Return flat list without grouping
                $this->employees = $enriched->map(function ($employee) {
                    $autBreakdown = $this->getAutBreakdown($employee);
                    $aut = collect($autBreakdown)->sum('amount');
                    $deductionBreakdowns = $this->getDeductionBreakdowns($employee);
                    $deductionBreakdowns['aut'] = $autBreakdown;
                    $deferredDeductionReference = $this->getDeferredCurrentDeductionReference($employee);

                    return [
                        'employee_no' => $employee->employee_no,
                        'name' => $employee->name,
                        'position' => $employee->position,
                        'monthly_rate' => $employee->monthly_rate,
                        'salary_earned' => $employee->basic_pay,
                        'aut' => $aut,
                        'aut_breakdown' => $autBreakdown,
                        'ut' => $employee->ut,
                        'absences' => $employee->absences,
                        'overtime' => $employee->overtime,
                        'holiday' => $employee->holiday,
                        'total_salary' => $employee->gross_pay - $aut,
                        'ewt_2' => $employee->ewt_2,
                        'percentage_tax_3' => $employee->percentage_tax_3,
                        'tax_ewt_5' => $employee->tax_ewt_5,
                        'w_tax' => $employee->w_tax,
                        'hmo' => $employee->hmo,
                        'deferred_deduction_reference' => $deferredDeductionReference,
                        'deferred_deduction_reference_total' => collect($deferredDeductionReference)->sum('amount'),
                        'deduction_breakdowns' => $deductionBreakdowns,
                        'deductions' => $employee->deductions,
                        'earnings' => $employee->earnings,
                        'adjustment' => $employee->salary_adjustment,
                        'net_salary' => $employee->net_pay,
                        'project_id' => $employee->project_id,
                        'remarks' => $employee->remarks ?? '',
                    ];
                });

            }
        }

        // PERMANENT
        if($employment_type_id == EmploymentTypesEnum::REGULAR->value) {
            $latestOrgDate = DB::table('employee_organization')
                ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
                ->when($payroll_date, fn ($query) => $query->whereDate('effectivity_date', '<=', $payroll_date))
                ->groupBy('employee_no');

            $latestOrgId = DB::table('employee_organization')
                ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
                ->groupBy('employee_no', 'effectivity_date');

            $pse = DB::table('payroll_salary_permanent_employees as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                    $join->on('pse.employee_no', '=', 'latest_org_date.employee_no');
                })
                ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                    $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                        ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
                })
                ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
                ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
                ->where('payroll_salary_id', $this->payroll_id)
                ->select(
                    'pse.*',
                    'ps.payroll_date',
                    'divisions.id as division_id',
                    'divisions.name as division_name'
                )
                ->get()
                ->map(function ($employee) {
                    $psped = DB::table('payroll_salary_permanents_employee_deductions')
                                    ->where('pspe_id', $employee->id)
                                    ->get();
                    [$netSalary15th, $netSalary30th] = $this->splitRegularNetPay((float) ($employee->net_pay ?? 0));

                    $employee->ut = 0;
                    $employee->absences = 0;
                    $employee->deductions = $psped;
                    $employee->net_salary_15th = $netSalary15th;
                    $employee->net_salary_30th = $netSalary30th;
                    $employee->division_name = $employee->division_name ?? 'No Division';

                    return $employee;
                });

            $this->employees = $pse;
        }

    }

    private function splitRegularNetPay(float $netPay): array
    {
        $firstCutoff = round($netPay / 2, 2);
        $secondCutoff = round($netPay - $firstCutoff, 2);

        return [$firstCutoff, $secondCutoff];
    }

    private function getAutBreakdown(object $employee): array
    {
        if (!$employee->apply_deduction) {
            return [];
        }

        $breakdown = [];
        $deductionOptions = $this->parseDeductionOptions($employee->deduction_apply_options ?? null);
        $hasExplicitDeductionOptions = !empty($deductionOptions);
        $applyCurrentDeduction = !$hasExplicitDeductionOptions || in_array('current', $deductionOptions, true);
        $currentAut = (float) ($employee->ut ?? 0) + (float) ($employee->absences ?? 0);

        if ($applyCurrentDeduction && $currentAut > 0) {
            $breakdown[] = [
                'label' => 'Current payroll',
                'period_covered' => $employee->period_covered ?? null,
                'payroll_no' => $employee->payroll_no ?? null,
                'url' => $employee->payroll_no
                    ? route('salary-pay.show', ['salary_pay' => $employee->payroll_no])
                    : null,
                'amount' => round($currentAut, 2),
            ];
        }

        return array_merge(
            $breakdown,
            $this->getDeferredCosAutBreakdown(
                $employee->employee_no,
                $employee->payroll_date,
                $employee->cutoff,
                $hasExplicitDeductionOptions
                    ? $this->selectedDeferredPayrollIds($deductionOptions)
                    : null
            )
        );
    }

    private function getDeferredCosAutBreakdown(string $employeeNo, ?string $payrollDate, ?string $cutoff, ?array $selectedPayrollIds = null): array
    {
        if (empty($payrollDate) || empty($cutoff)) {
            return [];
        }

        $date = Carbon::parse($payrollDate);

        return DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $employeeNo)
            ->where('ps.employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('ps.apply_deduction', false)
            ->when(
                is_array($selectedPayrollIds),
                fn ($query) => $query->whereIn('ps.id', $selectedPayrollIds ?: [0]),
                fn ($query) => $query
                    ->where('ps.deduction_deferred_cutoff', $cutoff)
                    ->whereYear('ps.deduction_deferred_date', $date->year)
                    ->whereMonth('ps.deduction_deferred_date', $date->month)
                    ->whereNull('ps.deduction_applied_payroll_id')
            )
            ->where('ps.status', '!=', 'cancelled')
            ->orderBy('ps.payroll_date')
            ->orderBy('ps.id')
            ->select(
                'ps.payroll_no',
                'ps.period_covered',
                'ps.payroll_date',
                'ps.cutoff',
                DB::raw('(COALESCE(pse.ut, 0) + COALESCE(pse.absences, 0)) as amount')
            )
            ->get()
            ->filter(fn ($row) => (float) $row->amount > 0)
            ->map(fn ($row) => [
                'label' => 'Deferred payroll',
                'period_covered' => $row->period_covered,
                'payroll_no' => $row->payroll_no,
                'payroll_date' => $row->payroll_date,
                'cutoff' => $row->cutoff,
                'url' => route('salary-pay.show', ['salary_pay' => $row->payroll_no]),
                'amount' => round((float) $row->amount, 2),
            ])
            ->values()
            ->all();
    }

    private function getDeductionBreakdowns(object $employee): array
    {
        $keys = ['ewt_2', 'percentage_tax_3', 'tax_ewt_5', 'w_tax', 'hmo'];
        $breakdowns = [
            'aut' => [],
            'ewt_2' => [],
            'percentage_tax_3' => [],
            'tax_ewt_5' => [],
            'w_tax' => [],
            'hmo' => [],
        ];

        if (!$employee->apply_deduction) {
            return $breakdowns;
        }

        $deductionOptions = $this->parseDeductionOptions($employee->deduction_apply_options ?? null);
        $hasExplicitDeductionOptions = !empty($deductionOptions);
        $applyCurrentDeduction = !$hasExplicitDeductionOptions || in_array('current', $deductionOptions, true);
        $selectedDeferredPayrollIds = $hasExplicitDeductionOptions
            ? $this->selectedDeferredPayrollIds($deductionOptions)
            : null;

        $deferred = $this->getDeferredCosTaxBreakdowns(
            $employee->employee_no,
            $employee->payroll_date,
            $employee->cutoff,
            $selectedDeferredPayrollIds
        );

        foreach ($keys as $key) {
            $deferredTotal = collect($deferred[$key] ?? [])->sum('amount');
            $currentAmount = round(max(0, (float) ($employee->{$key} ?? 0) - $deferredTotal), 2);

            if ($applyCurrentDeduction && $currentAmount > 0) {
                $breakdowns[$key][] = [
                    'label' => 'Current payroll',
                    'period_covered' => $employee->period_covered ?? null,
                    'payroll_no' => $employee->payroll_no ?? null,
                    'url' => $employee->payroll_no
                        ? route('salary-pay.show', ['salary_pay' => $employee->payroll_no])
                        : null,
                    'amount' => $currentAmount,
                ];
            }

            $breakdowns[$key] = array_merge($breakdowns[$key], $deferred[$key] ?? []);
        }

        return $breakdowns;
    }

    private function getDeferredCurrentDeductionReference(object $employee): array
    {
        $deductionOptions = $this->parseDeductionOptions($employee->deduction_apply_options ?? null);
        $hasExplicitDeductionOptions = !empty($deductionOptions);
        $currentDeductionApplies = $employee->apply_deduction
            && (!$hasExplicitDeductionOptions || in_array('current', $deductionOptions, true));

        if ($currentDeductionApplies) {
            return [];
        }

        $gross = (float) ($employee->gross_pay ?? 0);
        $taxFlags = $this->getCosTaxFlags($employee->employee_no);
        $amounts = [
            'aut' => round((float) ($employee->ut ?? 0) + (float) ($employee->absences ?? 0), 2),
            'ewt_2' => $taxFlags['two_percent'] ? round(max(0, $gross - 10417) * 0.02, 2) : 0,
            'percentage_tax_3' => $taxFlags['three_percent'] ? round($gross * 0.03, 2) : 0,
            'tax_ewt_5' => $taxFlags['five_percent'] ? round($gross * 0.05, 2) : 0,
            'hmo' => $this->getCosHmoAmount($employee->employee_no, $employee->payroll_date, $employee->cutoff),
        ];
        $amounts['w_tax'] = round($amounts['ewt_2'] + $amounts['percentage_tax_3'] + $amounts['tax_ewt_5'], 2);

        $labels = [
            'aut' => 'AUT',
            'ewt_2' => 'EWT (2%)',
            'percentage_tax_3' => 'Percentage Tax (3%)',
            'tax_ewt_5' => 'Tax (EWT: 5%)',
            'w_tax' => 'Overall Tax',
            'hmo' => 'HMO',
        ];

        return collect($labels)
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'amount' => $amounts[$key] ?? 0,
                'period_covered' => $employee->period_covered ?? null,
                'payroll_no' => $employee->payroll_no ?? null,
            ])
            ->filter(fn ($item) => (float) $item['amount'] > 0)
            ->values()
            ->all();
    }

    private function getDeferredCosTaxBreakdowns(string $employeeNo, ?string $payrollDate, ?string $cutoff, ?array $selectedPayrollIds = null): array
    {
        $breakdowns = [
            'ewt_2' => [],
            'percentage_tax_3' => [],
            'tax_ewt_5' => [],
            'w_tax' => [],
            'hmo' => [],
        ];

        if (empty($payrollDate) || empty($cutoff)) {
            return $breakdowns;
        }

        $date = Carbon::parse($payrollDate);
        $taxFlags = $this->getCosTaxFlags($employeeNo);

        $rows = DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $employeeNo)
            ->where('ps.employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('ps.apply_deduction', false)
            ->when(
                is_array($selectedPayrollIds),
                fn ($query) => $query->whereIn('ps.id', $selectedPayrollIds ?: [0]),
                fn ($query) => $query
                    ->where('ps.deduction_deferred_cutoff', $cutoff)
                    ->whereYear('ps.deduction_deferred_date', $date->year)
                    ->whereMonth('ps.deduction_deferred_date', $date->month)
                    ->whereNull('ps.deduction_applied_payroll_id')
            )
            ->where('ps.status', '!=', 'cancelled')
            ->orderBy('ps.payroll_date')
            ->orderBy('ps.id')
            ->select(
                'ps.payroll_no',
                'ps.period_covered',
                'ps.payroll_date',
                'ps.cutoff',
                'pse.gross_pay'
            )
            ->get();

        foreach ($rows as $row) {
            $gross = (float) ($row->gross_pay ?? 0);
            $amounts = [
                'ewt_2' => $taxFlags['two_percent'] ? round(max(0, $gross - 10417) * 0.02, 2) : 0,
                'percentage_tax_3' => $taxFlags['three_percent'] ? round($gross * 0.03, 2) : 0,
                'tax_ewt_5' => $taxFlags['five_percent'] ? round($gross * 0.05, 2) : 0,
                'hmo' => $this->getCosHmoAmount($employeeNo, $row->payroll_date, $row->cutoff),
            ];
            $amounts['w_tax'] = round($amounts['ewt_2'] + $amounts['percentage_tax_3'] + $amounts['tax_ewt_5'], 2);

            foreach ($amounts as $key => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $breakdowns[$key][] = [
                    'label' => 'Deferred payroll',
                    'period_covered' => $row->period_covered,
                    'payroll_no' => $row->payroll_no,
                    'payroll_date' => $row->payroll_date,
                    'cutoff' => $row->cutoff,
                    'url' => route('salary-pay.show', ['salary_pay' => $row->payroll_no]),
                    'amount' => $amount,
                ];
            }
        }

        return $breakdowns;
    }

    private function getCosTaxFlags(string $employeeNo): array
    {
        $flags = DB::table('employee_information')
            ->where('employee_no', $employeeNo)
            ->select('two_percent', 'three_percent', 'five_percent')
            ->first();

        return [
            'two_percent' => (bool) ($flags->two_percent ?? false),
            'three_percent' => (bool) ($flags->three_percent ?? false),
            'five_percent' => (bool) ($flags->five_percent ?? false),
        ];
    }

    private function getCosHmoAmount(string $employeeNo, ?string $payrollDate, ?string $cutoff): float
    {
        if (empty($payrollDate) || empty($cutoff)) {
            return 0;
        }

        $deductionApplied = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $payrollDate)
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id')
            ->value('deduction_applied');

        if ($cutoff !== $deductionApplied && $deductionApplied !== 'both') {
            return 0;
        }

        [$year, $month] = array_map('intval', explode('-', $payrollDate));

        $amount = (float) (DB::table('module_tab_employees as mte')
            ->where('mte.module_tab_id', 13)
            ->where('mte.employee_no', $employeeNo)
            ->where('mte.year', $year)
            ->where('mte.month', $month)
            ->value('amount') ?? 0);

        if ($deductionApplied === 'both') {
            return round($amount / 2, 2);
        }

        return round($amount, 2);
    }

    private function parseDeductionOptions(null|string|array $options): array
    {
        if (is_array($options)) {
            return $options;
        }

        if (!is_string($options) || $options === '') {
            return [];
        }

        $decoded = json_decode($options, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function selectedDeferredPayrollIds(array $options): array
    {
        return collect($options)
            ->filter(fn ($option) => is_string($option) && str_starts_with($option, 'payroll:'))
            ->map(fn ($option) => (int) substr($option, strlen('payroll:')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

}
