<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\StoreModuleTabEmployeeBulkRequest;
use App\Http\Requests\Admin\Modules\StoreModuleTabEmployeeRequest;
use App\Http\Requests\Admin\Modules\StorePhilhealthRequest;
use App\Services\Contributions\PhilhealthService;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleTabEmployeeController extends Controller
{
    protected $employeeService;
    protected $philhealthService;

    public function __construct(EmployeeService $employeeService, PhilhealthService $philhealthService)
    {
        $this->philhealthService = $philhealthService;
        $this->employeeService = $employeeService;
    }

    /**
     * Retrieve module tab employee data with monthly amounts for a given year.
     *
     * This method is responsible for building a year-based view of a specific
     * module tab (e.g., PhilHealth, Pag-IBIG, etc.) for all REGULAR / Permanent
     * employees.
     *
     * What this method does:
     * - Resolves the module and module tab using module and tab slugs
     * - Retrieves all REGULAR (Permanent) employees with organizational details
     * - Fetches module tab employee records for the specified year
     * - Maps monthly values (January–December) to each employee
     * - Defaults missing month values to 0
     *
     * Typical usage:
     * - Displaying contribution/deduction tables
     * - Payroll review per module tab
     * - Yearly contribution summaries
     *
     * Parameters:
     * @param  string  $slug   Module slug (e.g. "contributions")
     * @param  string  $tab    Module tab slug (e.g. "philhealth")
     * @param  int     $year   Target year (default: 2025)
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(string $slug, string $tab, int $year = 2025)
    {
        // Employment type ID for REGULAR / Permanent employees
        $regular_id = EmploymentTypesEnum::REGULAR->value;

        /**
         * Map numeric month values to human-readable month names.
         * These keys will be dynamically assigned to each employee object.
         */
        $monthNames = [
            1  => 'january',
            2  => 'february',
            3  => 'march',
            4  => 'april',
            5  => 'may',
            6  => 'june',
            7  => 'july',
            8  => 'august',
            9  => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        /**
         * Resolve module and module tab IDs using provided slugs.
         */
        $module = DB::table('modules as m')
            ->leftJoin('module_tabs as mt', 'm.id', '=', 'mt.module_id')
            ->select(
                'm.id as module_id',
                'mt.id as module_tab_id'
            )
            ->where('m.slug', $slug)
            ->where('mt.tab_slug', $tab)
            ->first();

        if (!$module) {
            // Return empty collection if module or tab is not found
            return collect();
        }

        /**
         * Fetch all REGULAR employees along with basic personal
         * and organizational information.
         */
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('eo.employment_type_id', $regular_id)
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

        $module_tab_id = $module->module_tab_id;

        /**
         * Fetch module tab employee records for the given year
         * and group them by employee number for faster lookup.
         */
        $module_tab_employees = DB::table('module_tab_employees')
            ->where('module_tab_id', $module_tab_id)
            ->where('year', $year)
            ->get()
            ->groupBy('employee_no');

        /**
         * Map employees with their corresponding monthly amounts.
         * Missing months are defaulted to zero.
         */
        return $employees->map(function ($employee) use ($module_tab_employees, $module_tab_id, $monthNames) {
            $employeeRecords = $module_tab_employees[$employee->employee_no] ?? [];

            foreach ($monthNames as $month => $monthName) {
                $record = collect($employeeRecords)->firstWhere('month', $month);
                $employee->{$monthName} = $record->amount ?? 0;
            }

            // Attach module tab ID for frontend or further processing
            $employee->module_tab_id = $module_tab_id;

            return $employee;
        });
    }


    /**
     * Store or update a module tab employee record for a specific month.
     *
     * This method creates or updates a payroll-related module tab entry
     * for a single employee, year, and month.
     *
     * Behavior:
     * - Accepts a human-readable month name (e.g. "January", "february")
     * - Converts the month name to its numeric equivalent (1–12)
     * - Performs an update-or-insert operation to avoid duplicate records
     * - Executes within a database transaction for data integrity
     *
     * Expected validated request fields:
     * - module_tab_id : integer (ID from module_tabs table)
     * - employee_no  : string  (Employee number)
     * - year         : integer (e.g. 2026)
     * - month        : string  (Month name, e.g. "January")
     * - amount       : float   (Computed amount to be stored)
     *
     * @param  StoreModuleTabEmployeeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreModuleTabEmployeeRequest $request)
    {
        $validatedData = $request->validated();

        /**
         * Map month name (string) to numeric month value.
         * Accepts case-insensitive input.
         */
        $monthNumbers = [
            'january'   => 1,
            'february'  => 2,
            'march'     => 3,
            'april'     => 4,
            'may'       => 5,
            'june'      => 6,
            'july'      => 7,
            'august'    => 8,
            'september' => 9,
            'october'   => 10,
            'november'  => 11,
            'december'  => 12,
        ];

        $monthNumber = $monthNumbers[strtolower($validatedData['month'])] ?? null;

        if (!$monthNumber) {
            return response()->json([
                'message' => 'Invalid month provided.',
                'errors'  => ['month' => ['Month must be a valid month name.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            /**
             * Update existing record or insert a new one
             * based on module tab, employee, year, and month.
             */
            $moduleTabEmployee = DB::table('module_tab_employees')->updateOrInsert(
                [
                    'module_tab_id' => $validatedData['module_tab_id'],
                    'employee_no'   => $validatedData['employee_no'],
                    'year'          => $validatedData['year'],
                    'month'         => $monthNumber,
                ],
                [
                    'amount' => $validatedData['amount'],
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Module tab employee record saved successfully.',
                'data'    => $moduleTabEmployee,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to store module tab employee record.',
                'error'   => $e->getMessage(),
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
     * @param  StoreModuleTabEmployeeBulkRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(StoreModuleTabEmployeeBulkRequest $request)
    {
        $payload = $request->validated();

        // Resolve module tab ID using tab_slug
        $moduleTabId = DB::table('module_tabs')
            ->where('tab_slug', $payload['module_tab'])
            ->value('id');

        if (!$moduleTabId) {
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
            $employeeNos = $this->employeeService
                ->getAllActiveEmployee(EmploymentTypesEnum::REGULAR->value);
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
        $start = Carbon::createFromFormat('Y-m', $payload['from_month'])->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $payload['to_month'])->startOfMonth();

        if ($end->lt($start)) {
            return response()->json([
                'message' => 'Invalid date range.',
                'errors'  => ['to_month' => ['To month must be after or equal to From month.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updated = 0;
            $skipped = [];

            foreach ($employeeNos as $employeeNo) {
                // Skip employees who are not REGULAR / Permanent
                if (!$this->checkIfPermanentEmployee($employeeNo)) {
                    $skipped[] = $employeeNo;
                    continue;
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
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $year  = (int) $cursor->year;
                    $month = (int) $cursor->month;

                    $ok = DB::table('module_tab_employees')->updateOrInsert(
                        [
                            'module_tab_id' => $moduleTabId,
                            'employee_no'   => $employeeNo,
                            'year'          => $year,
                            'month'         => $month,
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

                    $cursor->addMonthNoOverflow();
                }
            }

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
     * Bulk store PhilHealth computation for the whole year (Jan–Dec).
     *
     * This endpoint computes and stores PhilHealth amounts for ALL active
     * Permanent (REGULAR) employees for the selected year.
     *
     * What it does:
     * - Resolves the module tab by tab_slug (module_tab)
     * - Retrieves all active REGULAR employees
     * - Computes PhilHealth amount per employee using the provided computation string
     * - Saves/overwrites module_tab_employees records for months 1..12 of the given year
     *
     * Expected validated request fields:
     * - computation : string (format: rate,floor,ceiling) e.g. "5,10000,100000"
     * - year        : integer (e.g. 2026)
     * - module_tab  : string (must exist in module_tabs.tab_slug)
     *
     * Response:
     * - message: status message
     * - updated: number of rows affected (attempted upserts)
     * - skipped: non-permanent employees excluded
     *
     * @param  StorePhilhealthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function PhilhealthBulkStore(StorePhilhealthRequest $request)
    {
        $validatedData = $request->validated(); // computation, year, module_tab

        $moduleTabId = DB::table('module_tabs')
            ->where('tab_slug', $validatedData['module_tab'])
            ->value('id');

        if (!$moduleTabId) {
            return response()->json([
                'message' => 'Invalid module tab.',
                'errors'  => ['module_tab' => ['Module tab not found.']],
            ], 422);
        }

        $employeeNos = $this->employeeService
            ->getAllActiveEmployee(EmploymentTypesEnum::REGULAR->value);

        if (empty($employeeNos)) {
            return response()->json([
                'message' => 'No employees found.',
                'errors'  => ['employee_nos' => ['No active Permanent employees found.']],
            ], 422);
        }

        $year = (int) $validatedData['year'];
        $now  = now();

        DB::beginTransaction();

        try {
            $updated = 0;
            $skipped = [];

            foreach ($employeeNos as $employeeNo) {
                // keep consistent with your bulkStore behavior
                if (!$this->checkIfPermanentEmployee($employeeNo)) {
                    $skipped[] = $employeeNo;
                    continue;
                }

                $amount = $this->philhealthService->compute(
                    $employeeNo,
                    $validatedData['computation']
                );

                for ($month = 1; $month <= 12; $month++) {
                    DB::table('module_tab_employees')->updateOrInsert(
                        [
                            'module_tab_id' => $moduleTabId,
                            'employee_no'   => $employeeNo,
                            'year'          => $year,
                            'month'         => $month,
                        ],
                        [
                            'amount'     => $amount,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );

                    $updated++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'PhilHealth bulk computation saved successfully.',
                'year'    => $year,
                'months'  => 'January to December',
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
                'message' => 'PhilHealth bulk computation failed.',
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
        $employment_type_id = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->value('employment_type_id');

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
        $salary = DB::table('employee_salary')
            ->where('employee_no', $employee_no)
            ->orderByDesc('effectivity_date')
            ->value('amount');

        if (!is_numeric($salary) || !is_numeric($percentage) || $percentage <= 0) {
            return 0.0;
        }

        $salary = (float) $salary;
        $percentage = (float) $percentage;

        return round($salary * ($percentage / 100), 2);
    }

}
