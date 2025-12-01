<?php

namespace App\Services;

use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeePayrollComputationService {

    protected $daily_time_record_service;

    protected $employee_no;
    protected $payroll_id;

    protected $user_id;
    protected $name;
    protected $position;
    protected $salary_grade;

    protected $deduction_applied_on; // Determines whether the deduction would apply on the first or second cutoff
    protected $salary_basis;         // Defines whether the salary is based on a monthly rate or a daily rate
    protected $salary_amount;        // The employee's basic salary amount (monthly or daily, depending on salary_basis)
    protected $daily_rate;           // The computed daily rate based on the employee's salary
    protected $min_rate;

    protected $salary_frequency; // Indicates how often the employee receives salary (e.g., once or twice per month)
    protected $salary_cutoff;    // Specifies which cutoff applies (e.g., first or second), used only if salary is given once per month

    protected $employment_type; // regular or cos

    protected $start_date;
    protected $end_date;
    protected $payroll_date;
    protected $cutoff;

    protected $actual_presence;
    protected $shift_id;
    protected $work_schedule_id;
    protected $working_hours;

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function processEmployeeSalary($employee_no, $payroll_id) 
    {
        $this->employee_no = $employee_no;
        $this->payroll_id = $payroll_id;

        $this->getPayrollDetails();
        $this->getShiftAndWorkScheduleIds();
        $this->getEmployeeSalaryDetails();
        $this->getEmployeeInformation();
        $payload = [
            'user_id' => $this->user_id,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date
        ];

        if($this->salary_frequency === 'twice') {
            $this->salary_amount = $this->salary_amount / 2;
        }

        $dtr = $this->daily_time_record_service->getDTR($payload);

        $total_summary_of_dtr = $dtr['payroll_value'];

        $absences         = $total_summary_of_dtr['absent']; // day
        $late_undertime   = $total_summary_of_dtr['late_undertime']; // minutes
        $holiday_excess   = $total_summary_of_dtr['excess']; // percent ex. 1.5
        $overtime         = $total_summary_of_dtr['overtime']; // minutes
        $this->actual_presence  = $total_summary_of_dtr['actual_presence'];
        
        // Initialize
        $holiday_excess_amount = 0;

        // Compute components with full precision first
        if ($holiday_excess > 0) {
            $holiday_excess_amount = $this->daily_rate * $holiday_excess;
        }

        $basic_salary = $this->salary_amount;

        // --- Compute detailed amounts ---
        $overtime_amount        = round($this->min_rate * $overtime, 4);
        $holiday_excess_amount  = round($this->daily_rate * $holiday_excess, 4);
        $absences_amount        = round($this->daily_rate * $absences, 4);
        $late_undertime_amount  = round($this->min_rate * $late_undertime, 4);

        // $deductions = $this->getDeductions();
        // $sum_of_deduction = $deductions->sum('amount');

        //  -------------------------------------- COS PAYROLL ---------------------------------------------------
        if ($this->employment_type == EmploymentTypesEnum::COS->value) {
            // --- Compute totals (still full precision) ---
            $total_earnings   = $overtime_amount + $holiday_excess_amount;
            $total_deductions = $sum_of_deduction ?? 0;

            // --- Round for currency display / storage ---
            $basic_salary     = round($basic_salary, 2);
            $total_earnings   = round($total_earnings, 2);
            $total_deductions = round($total_deductions, 2);

            // --- Final computations ---
            $gross = round($basic_salary - $absences_amount - $late_undertime_amount + $total_earnings, 2);
            $net   = round($gross - $total_deductions, 2);

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
                Basic Salary    : {$basic_salary}
                Total Earnings  : {$total_earnings}
                Total Deductions: {$total_deductions}
                Gross Pay       : {$gross}
                Net Pay         : {$net}
                =============================================
            ");

            $pseId = DB::table('payroll_salary_employee')->insertGetId([
                'payroll_salary_id'     => $this->payroll_id,
                'employee_no'           => $this->employee_no,
                'name'                  => $this->name,
                'position'              => $this->position,
                'salary_grade'          => $this->salary_grade,
                'ut'                    => $late_undertime_amount,
                'absences'              => $absences_amount,
                'overtime'              => $overtime_amount,
                'holiday'               => $holiday_excess_amount,
                'gsis'                  => 0,
                'philhealth'            => 0,
                'pagibig'               => 0,
                'w_tax'                 => 0,
                'total_deductions'      => $total_deductions,
                'total_earnings'        => $total_earnings,
                'monthly_rate'          => round($this->salary_amount * 2, 2),
                'basic_pay'             => $basic_salary,
                'gross_pay'             => $gross,
                'net_pay'               => $net,
                'salary_adjustment'     => 0,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            Log::info("Insert ID returned: " . var_export($pseId, true));
        }

        //  -------------------------------------- PERMANENT PAYROLL ---------------------------------------------------
        if($this->employment_type ==  (int) EmploymentTypesEnum::REGULAR->value) {
            Log::info("Processing REGULAR employee: {$this->employee_no} for Payroll ID: {$this->payroll_id}");

            $deductions = $this->getDeductions($employee_no);

            $sum_of_deduction = round($deductions->sum('amount'), 2);

            $sum_of_deduction += $absences_amount + $late_undertime_amount;

            $net = $this->salary_amount - $sum_of_deduction;

            Log::info("
            =================== SALARY INFO ===================
            Payroll Salary ID   : " . $this->safe($this->payroll_id) . "
            Employee No         : " . $this->safe($this->employee_no) . "
            Name                : " . $this->safe($this->name) . "
            Position            : " . $this->safe($this->position) . "
            Salary Grade        : " . $this->safe($this->salary_grade) . "
            Monthly Rate x2     : " . (isset($this->salary_amount) ? round($this->salary_amount * 2, 2) : '') . "
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

            $pseId = DB::table('payroll_salary_permanent_employees')
                ->insertGetId(
                    [
                        'payroll_salary_id'     => $this->payroll_id, 
                        'employee_no'           => $this->employee_no,
                        'name'                  => $this->name,
                        'position'              => $this->position,
                        'monthly_rate'          => round($this->salary_amount * 2, 2),
                        'salary_grade'          => $this->salary_grade,

                        'ut'                    => $late_undertime_amount,
                        'absences'              => $absences_amount,
                        'overtime'              => $overtime_amount,
                        'holiday'               => $holiday_excess_amount,

                        'total_deductions'      => $sum_of_deduction,
                        'net_pay'               => $net,
                        'salary_adjustment'     => 0,
                        
                        'remarks'               => null,
                        'updated_at'            => now(),
                        'created_at'            => now(),
                    ]
                );

            foreach ($deductions as $deduction) {

                $deduction_name = $deduction->module_name . ' ' . $deduction->tab_name;

                Log::info("
                    ================ DEDUCTION INFO ================
                    Name            : {$deduction_name}
                    Amount          : {$deduction->amount}
                    ---------------------------------------------
                    Total Deductions: {$sum_of_deduction}
                    =============================================
                ");

                DB::table('payroll_salary_permanents_employee_deductions')->insert([
                    'pspe_id'  => $pseId,
                    'deduction_type' => $deduction_name,
                    'amount'         => $deduction->amount,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            $gross = $net + $sum_of_deduction;
            $total_deductions = $sum_of_deduction;
        } 

        return [
            'gross_amount' => $gross,
            'deduction_amount' => $total_deductions,
            'net_pay_amount' => $net,
        ];
 
    }

    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_salary')
                ->where('id', $this->payroll_id)
                ->first();

        if ($payroll) {
            $this->payroll_date = $payroll->payroll_date;
            $this->cutoff = $payroll->cutoff;

            if($this->cutoff == 'first_cutoff') {
                $this->start_date = date('Y-m-01', strtotime($this->payroll_date));
                $this->end_date = date('Y-m-15', strtotime($this->payroll_date));
            } else {
                $this->start_date = date('Y-m-16', strtotime($this->payroll_date));
                $this->end_date = date('Y-m-t', strtotime($this->payroll_date));
            }
        }

    }

    private function getEmployeeInformation()
    {
        $employee_information = DB::table('employee_organization')
                ->leftJoin('employee_information', 'employee_organization.employee_no', '=', 'employee_information.employee_no')
                ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
                ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
                ->select(
                    'employee_personal.firstname',
                    'employee_personal.middlename',
                    'employee_personal.lastname',
                    'employee_personal.suffix',
                    'employee_organization.employment_type_id',
                    'positions.name as position_name',
                    'users.id as user_id'
                )
                ->where('employee_organization.employee_no', $this->employee_no)
                ->first();

        if (!$employee_information) {
            throw new \Exception("Employee information not found for employee number: {$this->employee_no}");
        }

        $this->name = $employee_information->firstname . ' ' . 
            ($employee_information->middlename ? $employee_information->middlename . ' ' : '') . 
            $employee_information->lastname . 
            ($employee_information->suffix ? ' ' . $employee_information->suffix : '');
        $this->position = $employee_information->position_name;
        $this->employment_type = $employee_information->employment_type_id;
        $this->user_id = $employee_information->user_id;
    }

    private function getEmployeeSalaryDetails()
    {
        Log::info("Fetching salary details for employee number: {$this->employee_no} as of payroll date: {$this->payroll_date}");
        $employee_salary = DB::table('employee_salary')
            ->where('employee_no', $this->employee_no)
            ->whereDate('effectivity_date', '<=', $this->payroll_date) // 2025-10-23 <= 2025-09-23
            ->orderByDesc('effectivity_date')
            ->first();        

        if (!$employee_salary) {
            throw new \Exception("Employee salary not found for employee number: {$this->employee_no}");
        }

        Log::info('---------------------- here yon -----------------------');
        Log::info((array) $employee_salary);
        Log::info($this->working_hours);

        
        if ($employee_salary) {
           $this->deduction_applied_on = $employee_salary->deduction_applied;
            $this->salary_basis = $employee_salary->salary_basis;

            // Safely convert amount "51,304.00" → 51304.00
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

            // Compute min rate
            $this->min_rate = ($this->daily_rate / $this->working_hours) / 60;

            $this->salary_frequency = $employee_salary->salary_frequency;
            $this->salary_cutoff = $employee_salary->salary_cutoff;
            $this->salary_grade = $employee_salary->salary_grade;

            
            Log::info('=== EMPLOYEE SALARY LOADED ===', [
                'deduction_applied_on' => $this->deduction_applied_on,
                'salary_basis' => $this->salary_basis,
                'salary_amount' => $this->salary_amount,
                'daily_rate' => $this->daily_rate,
                'min_rate' => $this->min_rate,
                'salary_frequency' => $this->salary_frequency,
                'salary_cutoff' => $this->salary_cutoff,
                'salary_grade' => $this->salary_grade,
                'raw_employee_salary' => (array) $employee_salary // avoid stdClass logging error
            ]);
        }
    }

    private function getShiftAndWorkScheduleIds()
    {
        $schedule = DB::table('employee_shift_work_schedule as esw')
            ->leftJoin('shifts as s', 'esw.shift_id', '=', 's.id')
            ->select(
                'esw.shift_id',
                'esw.work_schedule_id',
                's.working_hours'
            )
            ->where('esw.employee_no', $this->employee_no)
            ->where('esw.effectivity_date', '<=', $this->payroll_date)
            ->first();

        if (!$schedule) {
            throw new \Exception('Please ask your HR to set your Shift and Work Schedule.');
        }

        $this->shift_id = $schedule->shift_id;
        $this->work_schedule_id = $schedule->work_schedule_id;
        $this->working_hours = $schedule->working_hours;
    }

    // permanent only
    private function getDeductions($employee_no)
    {
        $explodedStartDate = explode('-', $this->payroll_date);
        $year = (int) $explodedStartDate[0];
        $month = (int) $explodedStartDate[1];

        // Skip deduction if cutoff doesn't match
        if ($this->cutoff != $this->deduction_applied_on && $this->deduction_applied_on !== 'both') {
            return collect([]);
        }

        // Base deductions from module tabs
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

                // Remove module name prefix
                $item->tab_name = str_replace($item->module_name . ' ', '', $item->tab_name);

                return $item;
            });

        /**
         * Add Withholding Tax
         */
        $component_table_id = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::SALARY_ID->value)
            ->value('tax_id');

        $components_year_id = DB::table('payroll_components_years')
            ->where('payroll_component_id', $component_table_id)
            ->where('year', $year)
            ->value('id');

        $tax_table = DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $components_year_id)
            ->where('employee_no', $employee_no)
            ->where('month', $month)
            ->first();

        // Add tax as a new deduction
        if ($tax_table) {
            $deductions->push((object) [
                'module_name' => 'Witholding',
                'tab_name' => 'tax',
                'amount' => $tax_table->amount
            ]);
        }

        /**
         * Apply cutoff logic
         */
        // If once per month (first or second)
        if ($this->deduction_applied_on !== 'both') {

            // Deduct only once → no division
            return $deductions;
        }

        // If BOTH: divide all deductions into 2 parts
        $deductions = $deductions->map(function ($deduction) {
            $deduction->amount = $deduction->amount / 2;
            return $deduction;
        });

        return $deductions;
    }

    // Helper function to safely display values
    private function safe($value) {
        return isset($value) ? $value : '';
    }
}