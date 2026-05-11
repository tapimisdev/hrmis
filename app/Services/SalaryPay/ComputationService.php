<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use App\Enums\LeaveEnum;
use App\Enums\PayrollStatusEnum;
use App\Enums\TableSettingsEnum;
use App\Services\DailyTimeRecordService;
use App\Services\SalaryEmloyeeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class ComputationService
 *
 * Responsible for computing employee salary per payroll period.
 * Handles both:
 * - COS payroll (tax computations, inserts to payroll_salary_employee)
 * - REGULAR payroll (deductions + leave-credit handling, inserts to payroll_salary_permanent_employees)
 */
class ComputationService
{
    /**
     * DTR service used to fetch attendance/timekeeping summary for the payroll period.
     *
     * @var DailyTimeRecordService
     */
    protected $daily_time_record_service;
    protected $salaryEmployeeService;

    /** @var string|int */
    protected $employee_no;

    /** @var string|int */
    protected $payroll_id;

    /** @var int|null */
    protected $user_id;

    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $position;

    /** @var string|int|null */
    protected $salary_grade;

    /** @var string|int|null */
    protected $two_percent;

    /** @var string|int|null */
    protected $three_percent;

    /** @var string|int|null */
    protected $five_percent;

    /**
     * Determines whether the deduction would apply on the first or second cutoff
     * (or both).
     *
     * @var string|null
     */
    protected $deduction_applied_on;

    /**
     * Defines whether the salary is based on a monthly rate or a daily rate.
     *
     * @var string|null
     */
    protected $salary_basis;

    /**
     * Employee's basic salary amount (monthly or daily, depending on salary_basis).
     *
     * @var float|int|null
     */
    protected $salary_amount;

    /**
     * Computed daily rate based on the employee's salary.
     *
     * @var float|int|null
     */
    protected $daily_rate;

    /**
     * Computed per-minute rate based on daily_rate and working hours.
     *
     * @var float|int|null
     */
    protected $min_rate;

    /**
     * Salary frequency (e.g., once/twice per month).
     *
     * @var string|null
     */
    protected $salary_frequency;

    /**
     * Specifies which cutoff applies (used only if salary is given once per month).
     *
     * @var string|null
     */
    protected $salary_cutoff;

    /**
     * Employment type (regular or COS).
     *
     * @var int|string|null
     */
    protected $employment_type;

    /** @var string|null */
    protected $start_date;

    /** @var string|null */
    protected $end_date;

    /** @var string|null */
    protected $payroll_date;

    /** @var string|null */
    protected $cutoff;

    protected bool $apply_deduction = true;
    protected bool $apply_current_deduction = true;
    protected array $selected_deferred_payroll_ids = [];
    protected bool $has_explicit_deduction_options = false;

    /** @var mixed */
    protected $actual_presence;

    /** @var int|string|null */
    protected $shift_id;

    /** @var int|string|null */
    protected $work_schedule_id;

    /** @var int|float|null */
    protected $working_hours;

    /**
     * Inject required dependencies.
     */
    public function __construct(DailyTimeRecordService $daily_time_record_service, SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->daily_time_record_service = $daily_time_record_service;
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    /**
     * Compute and persist the salary record for a single employee under a payroll.
     *
     * Logic overview:
     * 1) Load payroll details (cutoff, date range)
     * 2) Load employee schedule (working hours)
     * 3) Load employee salary setup (rates, deduction cutoff settings)
     * 4) Load employee profile (name, position, employment type)
     * 5) Fetch DTR summary and compute:
     *    - absences (days), late/undertime (mins), overtime (mins), holiday excess (multiplier)
     * 6) Branch by employment type:
     *    - COS: compute taxes + insert payroll_salary_employee
     *    - REGULAR: compute deductions + insert payroll_salary_permanent_employees + insert deduction rows
     *
     * NOTE: Does not change output or existing business rules.
     */
    public function processEmployeeSalary($employee_no, $payroll_id)
    {
        /**
         * ============================================================
         * 1) Setup context
         * ============================================================
         */
        $this->employee_no = $employee_no;
        $this->payroll_id  = $payroll_id;

        // Load required data used by computations
        $this->getPayrollDetails();
        $this->getShiftAndWorkScheduleIds();
        $this->getEmployeeSalaryDetails();
        $this->getEmployeeInformation();

        /**
         * ============================================================
         * 2) Prepare DTR request payload
         * ============================================================
         */
        $payload = [
            'user_id'   => $this->user_id,
            'startDate' => $this->start_date,
            'endDate'   => $this->end_date,
        ];

        $remarks = null;

        /**
         * COS payroll remains cutoff-based. Regular/plantilla salary payroll is
         * monthly, so keep the full monthly salary amount for regular employees.
         */
        if ($this->employment_type == EmploymentTypesEnum::COS->value && $this->salary_frequency === 'twice') {
            $this->salary_amount = $this->salary_amount / 2;
        }

        /**
         * ============================================================
         * 3) Fetch DTR summary for the period
         * ============================================================
         */
        $dtr     = $this->daily_time_record_service->getDTR($payload);
        $summary = $dtr['payroll_value'];

        // DTR metrics
        $absences              = $summary['absent'];         // days
        $late_undertime        = $summary['late_undertime']; // minutes
        $holiday_excess        = $summary['excess'];         // multiplier (ex. 1.5)
        $overtime              = $summary['overtime'];       // minutes
        $this->actual_presence = $summary['actual_presence'];

        /**
         * ============================================================
         * 4) Base computations for earnings/deductions amounts
         * ============================================================
         */
        $basic_salary = $this->salary_amount;

        [$year, $month, $day] = explode('-', $this->payroll_date);

        // Amounts with payroll precision (4 decimals at this stage)
        $overtime_amount       = round($this->min_rate * $overtime, 4);
        $holiday_excess_amount = round($this->daily_rate * $holiday_excess, 4);

        // COS uses these as salary deductions. Regular/plantilla sets them to
        // zero before saving because AUT is handled outside regular payroll.
        $absences_amount       = round($this->daily_rate * $absences, 4);
        $late_undertime_amount = round($this->min_rate * $late_undertime, 4);

        /**
         * Defaults so return values are always defined.
         */
        $gross            = 0;
        $total_deductions = 0;
        $net              = 0;

        /**
         * ============================================================
         * 6) COS PAYROLL
         * ============================================================
         */
        if ($this->employment_type == EmploymentTypesEnum::COS->value) {
    $month = (int) $month;

    $toCents = fn(float $v): int => (int) round($v * 100, 0, PHP_ROUND_HALF_UP);
    $fromCents = fn(int $c): float => $c / 100;

    $percentOfCents = function (int $baseCents, float $rate): int {
        return (int) round($baseCents * $rate, 0, PHP_ROUND_HALF_UP);
    };

    // Earnings for COS are overtime + holiday excess only
    $total_earnings = $overtime_amount + $holiday_excess_amount;

    // Convert all money inputs to cents
    $basicC     = $toCents($basic_salary);
    $absencesC  = $toCents($absences_amount);
    $lateC      = $toCents($late_undertime_amount);
    $earningsC  = $toCents($total_earnings);

    if ($this->cutoff === 'second_cutoff') {
        $hmo = $this->getHmo($employee_no);
    } else {
        $hmo = 0;
    }

    $hmoC = $this->apply_current_deduction ? ($toCents($hmo) ?? 0) : 0;

    $autC = $absencesC + $lateC;
    $appliedAutC = $this->apply_current_deduction ? $autC : 0;

    // Gross = basic + earnings
    $grossC = $basicC + $earningsC;

    /**
     * ============================================================
     * FIXED: TAXABLE INCOME
     * taxable = gross - deductions (AUT + HMO)
     * ============================================================
     */
    $deductionsBaseC = $appliedAutC + $hmoC;
    $taxableC = $grossC - $deductionsBaseC;

    // 2% (with threshold 10417.00 pesos)
    $thresholdC = 10417 * 100;
    $base2C = max(0, $taxableC - $thresholdC);

    $ewt2C = $this->apply_current_deduction && $this->two_percent
        ? $percentOfCents($base2C, 0.02)
        : 0;

    // 3% — FIXED: use taxable income
    $ewt3C = $this->apply_current_deduction && $this->three_percent
        ? $percentOfCents($taxableC, 0.03)
        : 0;

    // 5% — FIXED: use taxable income
    $ewt5C = $this->apply_current_deduction && $this->five_percent
        ? $percentOfCents($taxableC, 0.05)
        : 0;

    if ($this->apply_deduction) {
        $deferredDeductions = $this->getDeferredCosDeductionCents(
            $employee_no,
            $toCents,
            $percentOfCents
        );

        $appliedAutC += $deferredDeductions['aut'];
        $ewt2C += $deferredDeductions['ewt_2'];
        $ewt3C += $deferredDeductions['percentage_tax_3'];
        $ewt5C += $deferredDeductions['tax_ewt_5'];
        $hmoC += $deferredDeductions['hmo'];
    }

    $totalDedC = $appliedAutC + $ewt2C + $ewt3C + $ewt5C + $hmoC;
    $netC      = $grossC - $totalDedC;

    // Convert back to pesos
    $basic_salary        = $fromCents($basicC);
    $total_earnings      = $fromCents($earningsC);
    $gross               = $fromCents($grossC);

    $ewt_2prct            = $fromCents($ewt2C);
    $percentage_tax_3prct = $fromCents($ewt3C);
    $tax_ewt_5prct        = $fromCents($ewt5C);

    $hmo                  = $fromCents($hmoC);
    $total_deductions     = $fromCents($totalDedC);
    $net                  = $fromCents($netC);

    Log::info("
        ================ SALARY INFO ================
        Name            : {$this->name}
        Position        : {$this->position}
        Daily Rate      : {$this->daily_rate}
        Monthly Rate    : {$this->salary_amount}
        Holiday Excess  : {$holiday_excess_amount}
        Absences        : {$absences}
        Absences Amount : {$absences_amount}
        UT mins         : {$late_undertime}
        UT Amount       : {$late_undertime_amount}
        Overtime mins   : {$overtime}
        Overtime Amount : {$overtime_amount}
        ---------------------------------------------
        Basic Salary        : {$basic_salary}
        Total Earnings      : {$total_earnings}
        Total Deductions    : {$total_deductions}
        Gross Pay           : {$gross}
        EWT 2%              : {$ewt_2prct}
        Percentage Tax 3%   : {$percentage_tax_3prct}
        Tax EWT 5%          : {$tax_ewt_5prct}
        hmo                 : {$hmo}
        Net Pay             : {$net}
        =============================================
    ");

    /**
     * Persist COS payroll entry.
     */
    $pseId = DB::table('payroll_salary_employee')->insertGetId([
        'payroll_salary_id' => $this->payroll_id,
        'employee_no'       => $this->employee_no,
        'name'              => $this->name,
        'position'          => $this->position,
        'salary_grade'      => $this->salary_grade,

        'ut'                => $late_undertime_amount,
        'absences'          => $absences_amount,
        'overtime'          => $overtime_amount,
        'holiday'           => $holiday_excess_amount,

        'gsis'              => 0,
        'philhealth'        => 0,
        'pagibig'           => 0,

        'ewt_2'             => $ewt_2prct,
        'percentage_tax_3'  => $percentage_tax_3prct,
        'tax_ewt_5'         => $tax_ewt_5prct,

        'w_tax'             => $ewt_2prct + $percentage_tax_3prct + $tax_ewt_5prct,

        'hmo'               => $hmo,

        'total_deductions'  => $total_deductions,
        'total_earnings'    => $total_earnings,

        'monthly_rate'      => round($this->salary_amount * 2, 2),
        'basic_pay'         => $basic_salary,
        'gross_pay'         => $gross,
        'net_pay'           => $net,

        'salary_adjustment' => 0,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    Log::info("Insert ID returned: " . var_export($pseId, true));
}

        /**
         * ============================================================
         * 7) PERMANENT / REGULAR PAYROLL
         * ============================================================
         */
        if ($this->employment_type == (int) EmploymentTypesEnum::REGULAR->value) {
            Log::info("Processing REGULAR employee: {$this->employee_no} for Payroll ID: {$this->payroll_id}");

            // Regular/plantilla payroll no longer carries AUT. Absences and
            // undertime are handled outside salary computation via leave/AUT.
            $absences_amount = 0;
            $late_undertime_amount = 0;

            /**
             * Fetch deductions for the employee (includes withholding tax prepended).
             */
            $deductions       = $this->getDeductions($employee_no);

            $sum_of_deduction = round($deductions->sum('amount'), 2);

            /**
             * Net pay calculation for permanent payroll.
             */
            $net = $this->salary_amount - $sum_of_deduction;

            // Salary diagnostics (unchanged content)
            Log::info("
            =================== SALARY INFO ===================
            Payroll Salary ID   : " . $this->safe($this->payroll_id) . "
            Employee No         : " . $this->safe($this->employee_no) . "
            Name                : " . $this->safe($this->name) . "
            Position            : " . $this->safe($this->position) . "
            Salary Grade        : " . $this->safe($this->salary_grade) . "
            Monthly Rate        : " . (isset($this->salary_amount) ? round($this->salary_amount, 2) : '') . "
            ---------------------------------------------------
            UT                  : " . $this->safe($late_undertime_amount) . "
            Absences            : " . $this->safe($absences_amount) . "
            Overtime            : " . $this->safe($overtime_amount) . "
            Holiday             : " . $this->safe($holiday_excess_amount) . "
            ---------------------------------------------------
            Total Deductions    : " . $this->safe($sum_of_deduction) . "
            Net Pay             : " . $this->safe($net) . "
            Salary Adjustment   : 0
            Remarks             : null
            Updated At          : " . now() . "
            Created At          : " . now() . "
            ==================================================
            ");

            /**
             * Insert permanent payroll record.
             */
            $pseId = DB::table('payroll_salary_permanent_employees')->insertGetId([
                'payroll_salary_id' => $this->payroll_id,
                'employee_no'       => $this->employee_no,
                'name'              => $this->name,
                'position'          => $this->position,
                'monthly_rate'      => round($this->salary_amount, 2),
                'salary_grade'      => $this->salary_grade,

                'ut'                => $late_undertime_amount,
                'absences'          => $absences_amount,
                'overtime'          => $overtime_amount,
                'holiday'           => $holiday_excess_amount,

                'total_deductions'  => $sum_of_deduction,
                'net_pay'           => $net,
                'salary_adjustment' => 0,

                'remarks'           => $remarks,

                'updated_at'        => now(),
                'created_at'        => now(),
            ]);

            /**
             * Insert each deduction breakdown row.
             */
            foreach ($deductions as $deduction) {
                $deduction_name = $deduction->tab_name;

                Log::info("
                    ================ DEDUCTION INFO ================
                    Name            : {$deduction_name}
                    Amount          : {$deduction->amount}
                    ---------------------------------------------
                    Total Deductions: {$sum_of_deduction}
                    =============================================
                ");

                DB::table('payroll_salary_permanents_employee_deductions')->insert([
                    'pspe_id'        => $pseId,
                    'deduction_type' => $deduction_name,
                    'amount'         => $deduction->amount,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Final totals for return payload
            $gross            = $net + $sum_of_deduction;
            $total_deductions = $sum_of_deduction;
        }

        /**
         * ============================================================
         * 8) Return totals (same output contract)
         * ============================================================
         */
        return [
            'gross_amount'     => $gross,
            'deduction_amount' => $total_deductions,
            'net_pay_amount'   => $net,
        ];
    }

    /**
     * Load payroll header details (payroll_date, cutoff) and compute
     * start_date/end_date range based on cutoff.
     */
    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_salary')
            ->where('id', $this->payroll_id)
            ->first();

        if ($payroll) {
            $this->payroll_date = $payroll->payroll_date;
            $this->cutoff       = $payroll->cutoff;
            $this->apply_deduction = (bool) ($payroll->apply_deduction ?? true);
            $deductionOptions = $this->parseDeductionOptions($payroll->deduction_apply_options ?? null);
            $this->has_explicit_deduction_options = !empty($deductionOptions);
            $this->apply_current_deduction = $this->apply_deduction
                && (!$this->has_explicit_deduction_options || in_array('current', $deductionOptions, true));
            $this->selected_deferred_payroll_ids = $this->selectedDeferredPayrollIds($deductionOptions);

            if ((string) $payroll->employment_type_id === EmploymentTypesEnum::REGULAR->value) {
                $this->start_date = date('Y-m-01', strtotime($this->payroll_date));
                $this->end_date   = date('Y-m-t', strtotime($this->payroll_date));
            } elseif ($this->cutoff == 'first_cutoff') {
                $this->start_date = date('Y-m-01', strtotime($this->payroll_date));
                $this->end_date   = date('Y-m-15', strtotime($this->payroll_date));
            } else {
                $this->start_date = date('Y-m-16', strtotime($this->payroll_date));
                $this->end_date   = date('Y-m-t', strtotime($this->payroll_date));
            }
        }
    }

    /**
     * Load employee profile data required for payroll:
     * - full name
     * - position
     * - employment type
     * - linked user_id (for DTR queries)
     */
    private function getEmployeeInformation()
    {
        $employee_information = $this->salaryEmployeeService->activeOrg($this->employee_no)
            ->leftJoin('employee_information', 'eo1.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'eo1.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->select(
                'employee_information.two_percent',
                'employee_information.three_percent',
                'employee_information.five_percent',
                'employee_personal.firstname',
                'employee_personal.middlename',
                'employee_personal.lastname',
                'employee_personal.suffix',
                'eo1.employment_type_id',
                'positions.name as position_name',
                'users.id as user_id'
            )
            ->first();

        if (!$employee_information) {
            throw new \Exception("Employee information not found for employee number: {$this->employee_no}");
        }

        $this->name =
            $employee_information->firstname . ' ' .
            ($employee_information->middlename ? $employee_information->middlename . ' ' : '') .
            $employee_information->lastname .
            ($employee_information->suffix ? ' ' . $employee_information->suffix : '');

        $this->position        = $employee_information->position_name;
        $this->employment_type = $employee_information->employment_type_id;
        $this->user_id         = $employee_information->user_id;

        $this->two_percent = $employee_information->two_percent;
        $this->three_percent = $employee_information->three_percent;
        $this->five_percent = $employee_information->five_percent;
    }

    /**
     * Load the employee salary record effective as of payroll_date.
     * Sets:
     * - salary basis & amount
     * - daily rate
     * - min rate (computed)
     * - deduction applied cutoff rules
     * - salary frequency & grade
     */
    private function getEmployeeSalaryDetails()
    {
        Log::info("Fetching salary details for employee number: {$this->employee_no} as of payroll date: {$this->payroll_date}");

        $employee_salary = $this->salaryEmployeeService
            ->activeSalary($this->employee_no, $this->payroll_date)
            ->first();

        if (!$employee_salary) {
            throw new \Exception("Employee salary not found for employee number: {$this->employee_no}");
        }

        Log::info((array) $employee_salary);
        Log::info($this->working_hours);

        $this->deduction_applied_on = $employee_salary->deduction_applied;
        $this->salary_basis         = $employee_salary->salary_basis;

        // Safely convert amounts like "51,304.00" → 51304.00
        $this->salary_amount = filter_var(
            $employee_salary->amount,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );

        // Safely convert daily rate even if "1,234.56"
        $this->daily_rate = filter_var(
            $employee_salary->daily_rate,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );

        // Compute per-minute rate: (daily_rate / working_hours / 60 mins)
        $this->min_rate = ($this->daily_rate / $this->working_hours) / 60;

        $this->salary_frequency = $employee_salary->salary_frequency;
        $this->salary_cutoff    = $employee_salary->salary_cutoff;
        $this->salary_grade     = $employee_salary->salary_grade;

        Log::info('=== EMPLOYEE SALARY LOADED ===', [
            'deduction_applied_on' => $this->deduction_applied_on,
            'salary_basis'         => $this->salary_basis,
            'salary_amount'        => $this->salary_amount,
            'daily_rate'           => $this->daily_rate,
            'min_rate'             => $this->min_rate,
            'salary_frequency'     => $this->salary_frequency,
            'salary_cutoff'        => $this->salary_cutoff,
            'salary_grade'         => $this->salary_grade,
            'raw_employee_salary'  => (array) $employee_salary,
        ]);
    }

    /**
     * Load the employee schedule record effective as of payroll_date.
     * Sets:
     * - shift_id
     * - work_schedule_id
     * - working_hours
     */
    private function getShiftAndWorkScheduleIds()
    {
        $schedule = $this->salaryEmployeeService
                    ->activeShift($this->employee_no, $this->payroll_date)
                    ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
                    ->select(
                        'sw1.shift_id',
                        'sw1.work_schedule_id',
                        's.working_hours',
                    )
                    ->first();

        if (!$schedule) {
            throw new \Exception('Please ask your HR to set your Shift and Work Schedule.');
        }

        $this->shift_id         = $schedule->shift_id;
        $this->work_schedule_id = $schedule->work_schedule_id;
        $this->working_hours    = $schedule->working_hours;
    }

    /**
     * Fetch employee deductions for permanent employees only.
     *
     * Includes:
     * - module tab deductions (module_tab_employees)
     * - withholding tax (employee_payroll_components), prepended at index 0 if exists
     *
     * Regular/plantilla payroll is monthly, so deductions are applied in full.
     */
    private function getDeductions($employee_no)
    {
        [$year, $month] = array_map('intval', explode('-', $this->payroll_date));

        /**
         * Base deductions from module tabs.
         */
        $deductions = DB::table('module_tab_employees as mte')
            ->leftJoin('module_tabs as mt', 'mte.module_tab_id', '=', 'mt.id')
            ->leftJoin('modules as m', 'mt.module_id', '=', 'm.id')
            ->select(
                'm.module_name',
                'mt.tab_name',
                'mte.amount'
            )
            ->where('mte.employee_no', $employee_no)
            ->where('mte.year', $year)
            ->where('mte.month', $month)
            ->get()
            ->map(function ($item) {
                // Remove module prefix from tab_name for cleaner deduction naming
                $item->tab_name = str_replace($item->module_name . ' ', '', $item->tab_name);
                return $item;
            });

        /**
         * Add Withholding Tax (as an additional deduction).
         */
        $component_table_id = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::SALARY_ID->value)
            ->value('tax_id');

        $components_year_id = DB::table('payroll_components_years')
            ->where('payroll_component_id', $component_table_id)
            ->where('year', $year)
            ->value('id');

        $tax_table = DB::table('employee_payroll_components')
            ->select('amount')
            ->where('tax_deduction_id', $components_year_id)
            ->where('employee_no', $employee_no)
            ->where('month', $month)
            ->first();

        // Prepend tax at index 0 if present
        if ($tax_table) {
            $deductions->prepend((object) [
                'module_name' => 'Withholding',
                'tab_name'    => 'Withholding tax',
                'amount'      => $tax_table->amount,
            ]);
        }

        return $deductions;
    }

    /**
     * Helper for logging / string building (avoid "undefined" prints).
     * NOTE: Retained as-is for behavior compatibility.
     */
    private function safe($value)
    {
        return isset($value) ? $value : '';
    }

    /**
     * Convert absences (days) and late/undertime (minutes) into:
     * - total minutes
     * - equivalent leave credits
     *
     * NOTE: Keeps existing computation and rounding behavior.
     */
    public function convertToMinutes($absences, $late_undertime): array
    {
        $workingHoursPerDay = $this->working_hours; // usually 8

        // Total minutes FIRST (no decimals here)
        $total_minutes = ($absences * $workingHoursPerDay * 60) + $late_undertime;

        // Leave credits based on 480 minutes == 1.0
        $leave_credits = $total_minutes / 480;

        return [
            'total_minutes'              => $total_minutes ?? 0,
            'equivalent_leave_credits'   => round($leave_credits, 3) ?? 0,
        ];
    }

    private function formatAutRemark(int $totalMinutes, float $workingHours, float $equivalent): string
    {
        $minutesPerDay = max(1, (int) round($workingHours * 60));
        $days = intdiv($totalMinutes, $minutesPerDay);
        $remainingMinutes = $totalMinutes % $minutesPerDay;
        $hours = intdiv($remainingMinutes, 60);
        $mins = $remainingMinutes % 60;

        return sprintf(
            'AUT %d day%s %d hour%s %d min%s = %.3f VL',
            $days,
            $days !== 1 ? 's' : '',
            $hours,
            $hours !== 1 ? 's' : '',
            $mins,
            $mins !== 1 ? 's' : '',
            $equivalent
        );
    }

    private function getDeferredCosDeductionCents(
        string $employee_no,
        callable $toCents,
        callable $percentOfCents
    ): array {
        $payrollDate = Carbon::parse($this->payroll_date);

        $deferredPayrolls = DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $employee_no)
            ->where('ps.employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('ps.apply_deduction', false)
            ->when(
                $this->has_explicit_deduction_options,
                fn ($query) => $query->whereIn('ps.id', $this->selected_deferred_payroll_ids ?: [0]),
                fn ($query) => $query
                    ->where('ps.deduction_deferred_cutoff', $this->cutoff)
                    ->whereYear('ps.deduction_deferred_date', $payrollDate->year)
                    ->whereMonth('ps.deduction_deferred_date', $payrollDate->month)
                    ->whereNull('ps.deduction_applied_payroll_id')
            )
            ->where('ps.status', '!=', 'cancelled')
            ->select(
                'ps.payroll_date',
                'ps.cutoff',
                'pse.gross_pay',
                'pse.ut',
                'pse.absences'
            )
            ->get();

        $totals = [
            'aut' => 0,
            'ewt_2' => 0,
            'percentage_tax_3' => 0,
            'tax_ewt_5' => 0,
            'hmo' => 0,
        ];

        foreach ($deferredPayrolls as $deferredPayroll) {
            $grossC = $toCents((float) ($deferredPayroll->gross_pay ?? 0));
            $thresholdC = 10417 * 100;
            $base2C = max(0, $grossC - $thresholdC);

            $totals['aut'] += $toCents((float) ($deferredPayroll->ut ?? 0))
                + $toCents((float) ($deferredPayroll->absences ?? 0));
            $totals['ewt_2'] += $this->two_percent ? $percentOfCents($base2C, 0.02) : 0;
            $totals['percentage_tax_3'] += $this->three_percent ? $percentOfCents($grossC, 0.03) : 0;
            $totals['tax_ewt_5'] += $this->five_percent ? $percentOfCents($grossC, 0.05) : 0;
            $totals['hmo'] += $toCents($this->getHmo(
                $employee_no,
                $deferredPayroll->payroll_date,
                $deferredPayroll->cutoff
            ));
        }

        return $totals;
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

    private function getHmo($employee_no, ?string $payrollDate = null, ?string $cutoff = null)
    {
        $payrollDate ??= $this->payroll_date;
        $cutoff ??= $this->cutoff;
        [$year, $month] = array_map('intval', explode('-', $payrollDate));

        // Skip deductions if cutoff doesn't match
        if ($cutoff != $this->deduction_applied_on && $this->deduction_applied_on !== 'both') {
            return 0;
        }

        $amount = DB::table('module_tab_employees as mte')
            ->where('mte.module_tab_id', 13)
            ->where('mte.employee_no', $employee_no)
            ->where('mte.year', $year)
            ->where('mte.month', $month)
            ->value('amount');

        // If null or empty, return 0.00
        if (is_null($amount)) {
            return 0.00;
        }

        // Convert to float and round to 2 decimals
        $amount = round((float) $amount, 2);

        // If applied on both cutoffs, divide by 2
        if ($this->deduction_applied_on === 'both') {
            return round($amount / 2, 2);
        }

        return $amount;
    }
}
