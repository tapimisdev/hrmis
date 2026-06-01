<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\StoreComponentEmployeeBulkRequest;
use App\Services\EmployeeService;
use App\Services\PayrollComponentService;
use App\Services\SalaryEmloyeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollComponentsEmployeeController extends Controller
{
    private const EDITABLE_PAYROLL_STATUSES = ['failed', 'cancelled'];

    protected $componentService;
    protected $employeeService;
    protected $salaryEmployeeService;

    public function __construct(PayrollComponentService $componentService, EmployeeService $employeeService, SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->componentService = $componentService;
        $this->employeeService = $employeeService;
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $slug, string $year)
    {
        
        $selectedEmployee = $request->query('employee_no', null);

        $component = DB::table('payroll_components')
                    ->where('slug', $slug)
                    ->first();

        $deduction = DB::table('payroll_components_years')
                    ->where('year', $year)
                    ->where('payroll_component_id', $component->id)
                    ->first();

        if(!$component) {
            abort(404);
        }

        $url = route('payroll-employee-components.index', ['slug' => $slug, 'year' => $year]);

        if(request()->wantsJson()) {

            $deduction_id = is_null($deduction) ? now()->year : $deduction->year;

            $pcs = DB::table('payroll_components_settings')
                ->where('tax_id', $component->id)
                ->value('type');

            $isCosComponent = in_array($pcs, ['ewt_2%', 'percentage_tax_3%', 'tax_ewt_5%']);

            $employees = $this->componentService->getAll(
                $component->id,
                $deduction_id,
                $isCosComponent ? 'cos' : 'regular'
            );

            $employees = $this->appendMonthEnableFlags($employees, (int) $year, $isCosComponent, $component->id);

            return response()->json($employees);
        }

        return view('admin.pages.payroll-components.employees.index', compact('component', 'slug', 'year', 'url', 'selectedEmployee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $slug, string $year)
    {
        // Get the tax based on slug
        $component = DB::table('payroll_components')->where('slug', $slug)->first();
        if (!$component) {
            abort(404, 'Tax not found');
        }

        // Get the deduction for this tax
        $deduction = DB::table('payroll_components_years')
            ->where('year', $year)
            ->where('payroll_component_id', $component->id)
            ->first();
        if (!$deduction) {
            abort(404);
        }

        // Validate input
        $validatedData = $request->validate([
            'id' => 'nullable|exists:employee_payroll_components,id',
            'month' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'employee_no' => 'required|exists:employee_information,employee_no',
        ]);

        // Map month name to number
        $monthNumbers = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];

        $monthNumber = $monthNumbers[strtolower($validatedData['month'])] ?? null;
        if (!$monthNumber) {
            return response([
                'message' => 'Invalid month provided',
                'status' => 'error',
            ], 422);
        }

        $pcs = DB::table('payroll_components_settings')
            ->where('tax_id', $component->id)
            ->value('type');

        $isCosComponent = in_array($pcs, ['ewt_2%', 'percentage_tax_3%', 'tax_ewt_5%']);

        if ($this->hasExistingSalaryPayroll($validatedData['employee_no'], (int) $year, $monthNumber, $component->id, $isCosComponent)) {
            return response()->json([
                'message' => 'Cannot update record. Salary payroll already exists for the specified employee, year, and month.',
                'errors'  => ['salary_payroll' => ['A salary payroll record exists for this employee, year, and month. Please remove it before updating this payroll component record.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            DB::table('employee_payroll_components')
                ->updateOrInsert(
                    [
                        'tax_deduction_id' => $deduction->id,
                        'employee_no' => $validatedData['employee_no'],
                        'month' => $monthNumber,
                    ],
                    [
                        'amount' => $validatedData['amount'],
                        'updated_at' => now(),
                    ]
                );

            DB::commit();

            return response([
                'message' => 'Employee tax data successfully added/updated',
                'status' => 'success',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status' => 'store/update fails',
            ], 500);
        }
    }

    /**
     * Bulk store module tab employee records.
     *
     * This method allows administrators to apply a fixed or percentage-based
     * amount to one or more employees across a specified month range.
     *
     * Supported behaviors:
     * - Accepts a list of employee numbers or the keyword "ALL"
     * - Applies only to REGULAR / Permanent employees
     * - Supports fixed amount or percentage-based computation
     * - Iterates through a month range (From → To)
     * - Creates or updates records using updateOrInsert
     * - Executes within a database transaction for data integrity
     *
     * Expected validated request fields:
     * - module_tab   : string  (tab_slug from module_tabs)
     * - employee_nos: string  (comma-separated list or "ALL")
     * - from_month  : string  (Y-m format, e.g. 2026-01)
     * - to_month    : string  (Y-m format, e.g. 2026-12)
     * - amount      : float   (fixed amount or percentage value)
     * - amount_type : string  ("fixed" or "percent")
     *
     * Response:
     * - message : status message
     * - updated : number of records affected
     * - skipped : employees excluded (non-permanent)
     *
     * @param  StoreComponentEmployeeBulkRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(StoreComponentEmployeeBulkRequest $request)
    {
        $payload = $request->validated();
        $isCosComponent = in_array($payload['module_tab'], ['ewt-2', 'percentage-tax-3', 'tax-ewt-5']);

        // Resolve module tab ID using tab_slug
        $payroll_component_years_id = DB::table('payroll_components_years')
            ->where('payroll_component_id', $payload['id'])
            ->where('year', $payload['year'])
            ->value('id');

        if (!$payroll_component_years_id) {
            return response()->json([
                'message' => 'Invalid module tab.',
                'errors'  => ['module_tab' => ['Module tab not found.']],
            ], 422);
        }

        /**
         * Resolve employee list:
         * - "ALL" applies to all active REGULAR employees
         * - Otherwise, use a cleaned list of employee numbers
         */
        $raw = trim($payload['employee_nos'] ?? '');

        if (strtoupper($raw) === 'ALL') {
            
            // if the tax type is cos, get all cos employees
            if ($isCosComponent) {
                $employeeNos = $this->employeeService
                    ->getAllActiveEmployee(EmploymentTypesEnum::COS->value);
            } else {
                $employeeNos = $this->employeeService
                    ->getAllActiveEmployee(EmploymentTypesEnum::REGULAR->value);
            }

        } else {
            $employeeNos = collect(explode(',', $raw))
                ->map(fn($v) => trim($v))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($employeeNos)) {
            return response()->json([
                'message' => 'No employee numbers provided.',
                'errors'  => ['employee_nos' => ['Please provide at least one employee number.']],
            ], 422);
        }

        /**
         * Resolve month range.
         * The loop iterates from the starting month to the ending month (inclusive).
         */
        $start = (int) $payload['from_month']; // 1–12
        $end   = (int) $payload['to_month'];   // 1–12

        if ($end < $start) {
            return response()->json([
                'message' => 'Invalid date range.',
                'errors'  => [
                    'to_month' => ['To month must be after or equal to From month.']
                ],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updated = 0;
            $skipped = [];

            foreach ($employeeNos as $employeeNo) {
                // Skip employees who are not REGULAR / Permanent

                // if the tax type is cos, get all cos employees
                if ($isCosComponent) {
                    if ($this->checkIfPermanentEmployee($employeeNo)) {
                        $skipped[] = $employeeNo;
                        continue;
                    }
                } else {
                    if (!$this->checkIfPermanentEmployee($employeeNo)) {
                        $skipped[] = $employeeNo;
                        continue;
                    }
                }

                // Determine amount based on type
                if ($payload['amount_type'] === 'percent') {
                    $amount = $this->computePercentageSalary(
                        $employeeNo,
                        (float) $payload['amount']
                    );
                } else {
                    $amount = (float) $payload['amount'];
                }

                // Apply amount across the month range
               for ($month = $start; $month <= $end; $month++) {
                    if ($this->hasExistingSalaryPayroll($employeeNo, (int) $payload['year'], $month, (int) $payload['id'], $isCosComponent)) {
                        continue;
                    }

                    $ok = DB::table('employee_payroll_components')->updateOrInsert(
                        [
                            'tax_deduction_id' => $payroll_component_years_id,
                            'employee_no'      => $employeeNo,
                            'month'            => $month, // 1..12
                        ],
                        [
                            'amount'     => $amount,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    if ($ok) {
                        $updated++;
                    }
                }
            }

            // dd($updated, $skipped, $employeeNos);
            DB::commit();

            return response()->json([
                'message' => 'Bulk update successful.',
                'updated' => $updated,
                'skipped' => [
                    'count'     => count($skipped),
                    'employees' => $skipped,
                    'reason'    => 'Not REGULAR / not permanent',
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Bulk update failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if an employee is considered Permanent (REGULAR).
     *
     * This method determines whether an employee belongs to the
     * REGULAR employment type based on their organizational record.
     *
     * Business Rule:
     * - Only employees with employment_type_id matching
     *   EmploymentTypesEnum::REGULAR are treated as Permanent.
     *
     * Used in:
     * - Bulk contribution processing
     * - Payroll filtering
     * - Government contribution eligibility checks
     *
     * @param  string  $employee_no
     * @return bool
     */
    private function checkIfPermanentEmployee(string $employee_no): bool
    {
        $employment_type_id = $this->salaryEmployeeService->activeOrg($employee_no)->value('employment_type_id');
        
        return (int) $employment_type_id === (int) EmploymentTypesEnum::REGULAR->value;
    }

    /**
     * Compute a percentage-based amount from the employee's salary.
     *
     * This method calculates a monetary value based on a percentage
     * of the employee's latest salary record.
     *
     * Example:
     * - Salary: 30,000
     * - Percentage: 10
     * - Result: 3,000.00
     *
     * Rules:
     * - Uses the most recent salary (by effectivity_date)
     * - Returns 0.00 if salary is missing or percentage is zero/negative
     * - Result is rounded to two decimal places
     *
     * Used in:
     * - Percentage-based deductions
     * - Payroll components computed from salary
     *
     * @param  string  $employee_no
     * @param  float   $percentage
     * @return float
     */
    private function computePercentageSalary(string $employee_no, float $percentage): float
    {
        $salaryRaw = $this->salaryEmployeeService->activeSalary($employee_no)->value('amount');

        if (!$salaryRaw || $percentage <= 0) {
            return 0.0;
        }

        $salaryClean = preg_replace('/[^0-9.\-]/', '', (string) $salaryRaw);

        if (!is_numeric($salaryClean)) {
            throw new \RuntimeException(
                "Invalid salary format for {$employee_no}: " . var_export($salaryRaw, true)
            );
        }

        $salary = (float) $salaryClean;

        return round($salary * ($percentage / 100), 2);
    }

    private function appendMonthEnableFlags($employees, int $year, bool $isCosComponent, int $componentId)
    {
        $employeeNos = collect($employees)->pluck('employee_no')->filter()->values();
        $locks = $this->salaryPayrollLocks($employeeNos, $year, $componentId, $isCosComponent);
        $monthKeys = [
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
        $source = $this->payrollSource($componentId, $isCosComponent);

        return collect($employees)->map(function ($employee) use ($locks, $monthKeys, $source) {
            $employeeLocks = collect($locks[$employee->employee_no] ?? []);
            $lockedMonths = $employeeLocks
                ->filter(fn ($lock) => !$this->isEditablePayrollStatus($lock->payroll_status ?? null))
                ->pluck('payroll_month')
                ->map(fn ($month) => (int) $month)
                ->all();

            foreach ($monthKeys as $month => $monthKey) {
                $lock = $employeeLocks->firstWhere('payroll_month', $month);
                $employee->{$monthKey . '_enable'} = !in_array($month, $lockedMonths, true);
                $employee->{$monthKey . '_payroll_status'} = $lock->payroll_status ?? null;
                $employee->{$monthKey . '_payroll_source'} = $lock->payroll_source ?? $source['label'];
            }

            return $employee;
        });
    }

    private function hasExistingSalaryPayroll(string $employeeNo, int $year, int $month, int $componentId, bool $isCosComponent): bool
    {
        $source = $this->payrollSource($componentId, $isCosComponent);

        return DB::table($source['employee_table'] . ' as pe')
            ->join($source['payroll_table'] . ' as p', 'pe.' . $source['foreign_key'], '=', 'p.id')
            ->where('pe.employee_no', $employeeNo)
            ->whereYear('p.' . $source['date_column'], $year)
            ->whereMonth('p.' . $source['date_column'], $month)
            ->whereNotIn('p.status', self::EDITABLE_PAYROLL_STATUSES)
            ->exists();
    }

    private function salaryPayrollLocks($employeeNos, int $year, int $componentId, bool $isCosComponent)
    {
        $source = $this->payrollSource($componentId, $isCosComponent);

        $emps = DB::table($source['employee_table'] . ' as pe')
            ->join($source['payroll_table'] . ' as p', 'pe.' . $source['foreign_key'], '=', 'p.id')
            ->whereIn('pe.employee_no', $employeeNos)
            ->whereRaw("LEFT(p.{$source['date_column']}, 4) = ?", [$year])
            ->orderByDesc('p.id')
            ->select(
                'pe.employee_no',
                DB::raw("CAST(SUBSTRING(p.{$source['date_column']}, 6, 2) AS UNSIGNED) as payroll_month"),
                'p.status as payroll_status',
                DB::raw("'" . $source['label'] . "' as payroll_source")
            )
            ->get()
            ->groupBy('employee_no');

        return $emps;
    }

    private function payrollSource(int $componentId, bool $isCosComponent): array
    {
        $type = DB::table('payroll_components_settings')
            ->where(function ($query) use ($componentId) {
                $query->where('table_id', $componentId)
                    ->orWhere('tax_id', $componentId);
            })
            ->orderByDesc('id')
            ->value('type');

        switch ($type) {
            case 'transportation_allowance':
            case 'representation_allowance':
            case 'pera_allowance':
            case 'rata_allowance':
                return [
                    'employee_table' => 'payroll_pera_rata_employee',
                    'payroll_table' => 'payroll_pera_rata',
                    'foreign_key' => 'payroll_pera_rata_id',
                    'date_column' => 'month',
                    'label' => 'PERA / RATA Payroll',
                ];

            case 'hazard_pay':
                return [
                    'employee_table' => 'payroll_hazard_pay_employee',
                    'payroll_table' => 'payroll_hazard_pay',
                    'foreign_key' => 'payroll_hazard_pay_id',
                    'date_column' => 'month',
                    'label' => 'Hazard Payroll',
                ];

            case 'longetivity_pay':
                return [
                    'employee_table' => 'payroll_longevity_pay_employee',
                    'payroll_table' => 'payroll_longevity_pay',
                    'foreign_key' => 'payroll_longevity_pay_id',
                    'date_column' => 'month',
                    'label' => 'Longevity Payroll',
                ];

            default:
                return [
                    'employee_table' => $isCosComponent
                        ? 'payroll_salary_employee'
                        : 'payroll_salary_permanent_employees',
                    'payroll_table' => 'payroll_salary',
                    'foreign_key' => 'payroll_salary_id',
                    'date_column' => 'payroll_date',
                    'label' => 'Salary Payroll',
                ];
        }
    }

    private function isEditablePayrollStatus(?string $status): bool
    {
        return in_array($status, self::EDITABLE_PAYROLL_STATUSES, true);
    }
}
