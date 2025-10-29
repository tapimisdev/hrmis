<?php

namespace App\Services;

use App\Enums\EmploymentTypesEnum;
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

    protected $deduction_applied_on; // deduct on first or second cutoff
    protected $salary_basis; // monthly or daily
    protected $salary_amount; // basic salary amount
    protected $daily_rate; // basic salary amount
    protected $min_rate;

    protected $salary_frequency; // once or twice per month
    protected $salary_cutoff; // first or second cutoff when salary frequency is once per month

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

        $absences         = $total_summary_of_dtr['absent'];
        $late_undertime   = $total_summary_of_dtr['late_undertime'];
        $holiday_excess   = $total_summary_of_dtr['excess'];
        $overtime         = $total_summary_of_dtr['overtime'];
        $this->actual_presence  = $total_summary_of_dtr['actual_presence'];

        $deductions = $this->getDeductions();
        $sum_of_deduction = $deductions->sum('amount');
        
        if ($this->employment_type == EmploymentTypesEnum::COS->value) {
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

            // --- Compute totals (still full precision) ---
            $total_earnings   = $overtime_amount + $holiday_excess_amount;
            $total_deductions = $sum_of_deduction;

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

            foreach ($deductions as $deduction) {
                Log::info("
                    ================ DEDUCTION INFO ================
                    Name            : {$deduction->deduction_type}
                    Type            : {$deduction->type}
                    Cut Off         : {$deduction->cutoff}
                    Amount          : {$deduction->amount}
                    ---------------------------------------------
                    Total Deductions: {$sum_of_deduction}
                    =============================================
                ");

                DB::table('payroll_salary_employee_edeductions')->insert([
                    'payroll_se_id'  => $pseId,
                    'deduction_type' => $deduction->deduction_type,
                    'amount'         => $deduction->amount,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        if($this->employment_type == EmploymentTypesEnum::REGULAR->value) {
            Log::info("Processing REGULAR employee: {$this->employee_no} for Payroll ID: {$this->payroll_id}");

            $earnings = $this->getEarnings();
            $sum_of_earnings = $earnings->sum('amount');
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
        
        if ($employee_salary) {
            $this->deduction_applied_on = $employee_salary->deduction_applied;
            $this->salary_basis = $employee_salary->salary_basis;
            $this->salary_amount = $employee_salary->amount;
            $this->daily_rate = $employee_salary->daily_rate;
            $this->min_rate = ($employee_salary->daily_rate / $this->working_hours) / 60; 
            $this->salary_frequency = $employee_salary->salary_frequency;
            $this->salary_cutoff = $employee_salary->salary_cutoff;
            $this->salary_grade = $employee_salary->salary_grade;
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

    private function getDeductions()
    {
        $date = $this->payroll_date;

        $deductions = DB::table('employee_deductions as ed')
            ->leftJoin('deductions as d', 'ed.deduction_id', '=', 'd.id')
            ->where('ed.employee_no', $this->employee_no)
            ->where('ed.isActive', true)
            ->whereDate('ed.start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereDate('ed.end_date', '>=', $date)
                    ->orWhereNull('ed.end_date');
            })
            ->select(
                'd.name',
                'ed.first_term',
                'ed.second_term',
                'ed.type',
                'ed.start_date',
                'ed.end_date'
            )
            ->get();

        $enriched = $deductions->map(function ($d) {
            $amount_to_deduct = 0;

            if (isset($this->cutoff)) {
                $amount_to_deduct = match ($this->cutoff) {
                    'first_cutoff'  => $d->first_term ?? 0,
                    'second_cutoff' => ($d->type === 'monthly') ? ($d->second_term ?? 0) : ($d->first_term ?? 0),
                    default => 0,
                };

                if ($d->type === 'daily') {
                    $amount_to_deduct *= $this->actual_presence;
                } elseif ($d->type === 'divided_by_22') {
                    $amount_to_deduct = ($amount_to_deduct / 22) * $this->actual_presence;
                }
            }

            return (object) [
                'deduction_type' => $d->name,
                'type'           => $d->type,
                'cutoff'         => $this->cutoff,
                'amount'         => $amount_to_deduct,
            ];
        });

        return $enriched;
    }


    private function getEarnings()
    {
        $date = $this->payroll_date;

        $deductions = DB::table('employee_earnings as ee')
            ->leftJoin('earnings as e', 'ee.earning_id', '=', 'e.id')
            ->where('ee.employee_no', $this->employee_no)
            ->where('ee.isActive', true)
            ->whereDate('ee.start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereDate('ee.end_date', '>=', $date)
                    ->orWhereNull('ee.end_date');
            })
            ->select(
                'e.name',
                'ee.first_term',
                'ee.second_term',
                'ee.type',
                'ee.isTaxable',
                'ee.start_date',
                'ee.end_date',
            )
            ->get();

        $enriched = $deductions->map(function ($d) {
            $amount_to_add = 0;

            if (isset($this->cutoff)) {
                $amount_to_add = match ($this->cutoff) {
                    'first_cutoff'  => $d->first_term ?? 0,
                    'second_cutoff' => ($d->type === 'monthly') ? ($d->second_term ?? 0) : ($d->first_term ?? 0),
                    default => 0,
                };

                if ($d->type === 'daily') {
                    $amount_to_add *= $this->actual_presence;
                } elseif ($d->type === 'divided_by_22') {
                    $amount_to_add = ($amount_to_add / 22) * $this->actual_presence;
                }
            }

            return (object) [
                'is_taxable'     => $d->isTaxable,
                'deduction_type' => $d->name,
                'type'           => $d->type,
                'cutoff'         => $this->cutoff,
                'amount'         => $amount_to_add,
            ];
        });

        return $enriched;
    }

    private function computeWithHoldingTax()
    {

    }

}