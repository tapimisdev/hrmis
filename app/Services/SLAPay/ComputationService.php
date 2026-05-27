<?php

namespace App\Services\SLAPay;

use App\Services\DailyTimeRecordService;
use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Enums\TableSettingsEnum;
use App\Services\SalaryEmloyeeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComputationService {

    protected $daily_time_record_service;
    protected $salaryEmployeeService;

    protected $employee_no;
    protected $payroll_id;

    protected $user_id;
    protected $name; 
    protected $employment_type;
    protected $position;
    protected $salary_amount;
    protected $payroll_date;

    protected $actual_presence;
    protected $shift_id;
    protected $work_schedule_id;
    protected $working_hours;
    protected $withHoldingTax;
    protected $start_date;
    protected $end_date;

    public function __construct(DailyTimeRecordService $daily_time_record_service, SalaryEmloyeeService $salaryEmployeeService) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    private function computeUTDeduction(int $minutes): int
    {
        return match (true) {
            $minutes >= 241 => 150,   // 4 hrs 1 min and above
            $minutes >= 181 => 75,    // 3 hrs 1 min to 4 hrs
            $minutes >= 121 => 50,    // 2 hrs 1 min to 3 hrs
            $minutes >= 1   => 25,    // 1 min to 2 hrs
            default         => 0,     // 0 min
        };
    }

    public function process($employee_no, $payroll_id) 
    {
        $this->employee_no = $employee_no;
        $this->payroll_id = $payroll_id;

        $this->getPayrollDetails();
        $this->getShiftAndWorkScheduleIds();
        $this->getEmployeeSalaryDetails();
        $this->getEmployeeInformation();

        $subsistenceRecord = $this->getSubsistenceAllowanceRecord();
        $subsistenceRemarks = null;

        if ($subsistenceRecord) {
            $actual_presence = (float) $subsistenceRecord->actual_days;
            $total_ut = 0;
            $subsistence_allowance = (float) $subsistenceRecord->computed_amount;
            $subsistenceRemarks = $this->buildSubsistenceRemarks($subsistenceRecord);
        } else {
            $payload = [
                'user_id'   => $this->user_id,
                'startDate' => Carbon::parse($this->start_date)->format('Y-m-d'),
                'endDate'   => Carbon::parse($this->end_date)->format('Y-m-d'),
            ];

            $dtr = $this->daily_time_record_service->getDTR($payload);
            $total_summary_of_dtr = $dtr['payroll_value'];
            $actual_presence = $total_summary_of_dtr['actual_presence'];
            $total_ut = $total_summary_of_dtr['late_undertime'] ?? 0;
            $subsistence_allowance = 150 * $actual_presence;
        }

        $laundry_allowance = (500 / 22) * $actual_presence;
        $total_sla = $subsistence_allowance + $laundry_allowance;
        $ut_deductions = $this->computeUTDeduction($total_ut);

        $uniform_deduction = 350;
        $less_healthcard = 0;
        $total = max($total_sla - $ut_deductions - $uniform_deduction, 0);
        $netPay = max($total - $less_healthcard, 0);

        DB::table('payroll_sla_pay_employee')
            ->insert([
                'payroll_sla_pay_id' => $this->payroll_id,
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'position' => $this->position,
                'subsistence_allowance' => $subsistence_allowance,
                'laundry_allowance' => $laundry_allowance,
                'total_sla' => $total_sla,
                'ut_deductions' => $ut_deductions,
                'uniform_deduction' => $uniform_deduction,
                'total' => $total,
                'healthcard' => $less_healthcard,
                'adjustments' => 0,
                'net_pay' => $netPay,
                'remarks' => $subsistenceRemarks,
            ]);

        return [
            'net_pay' => $netPay,
        ];
    }

    private function getSubsistenceAllowanceRecord()
    {
        $serviceMonth = Carbon::parse($this->start_date);

        return DB::table('subsistence_allowance_records')
            ->where('employee_no', $this->employee_no)
            ->where('month', (int) $serviceMonth->format('n'))
            ->where('year', (int) $serviceMonth->format('Y'))
            ->first();
    }

    private function buildSubsistenceRemarks($record): ?string
    {
        $remarks = [];
        $actualDays = (float) ($record->actual_days ?? 0);
        $deductionCount = (float) ($record->deduction_count ?? 0);
        $deductionAmount = (float) ($record->deduction_amount ?? 0);
        $manualRemarks = trim((string) ($record->remarks ?? ''));

        $remarks[] = 'Actual days: ' . $this->formatNumber($actualDays);

        if ($deductionCount > 0 || $deductionAmount > 0) {
            $remarks[] = 'Deduction: ' . $this->formatNumber($deductionCount) . ' / PHP ' . number_format($deductionAmount, 2);
        }

        if ($manualRemarks !== '') {
            $remarks[] = $manualRemarks;
        }

        return implode("\n", $remarks);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_sla_pay')
            ->where('id', $this->payroll_id)
            ->first();

        if (!$payroll) {
            throw new \Exception("Payroll not found for ID: {$this->payroll_id}");
        }

        $this->payroll_date = $payroll->month; 
        $this->start_date = date('Y-m-01', strtotime($this->payroll_date . '-01'));
        $this->end_date = date('Y-m-t', strtotime($this->payroll_date . '-01'));

        Log::info("Payroll Details - Start: {$this->start_date}, End: {$this->end_date}, Month: {$this->payroll_date}");
    }

    private function getShiftAndWorkScheduleIds()
    {
        $year = substr($this->payroll_date, 0, 4);
        $month = substr($this->payroll_date, 5, 2);
        $cutoffDate = Carbon::create($year, $month, 1)->endOfMonth();

        $schedule = $this->salaryEmployeeService
                        ->activeShift($this->employee_no, $cutoffDate)
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

        $this->shift_id = $schedule->shift_id;
        $this->work_schedule_id = $schedule->work_schedule_id;
        $this->working_hours = $schedule->working_hours;
    }

    private function getEmployeeSalaryDetails()
    {
        Log::info("Fetching salary details for employee number: {$this->employee_no} as of payroll month: {$this->payroll_date}");

        $cutoff = $this->payroll_date . '-31';

        $employee_salary = $this->salaryEmployeeService
            ->activeSalary($this->employee_no, $cutoff)
            ->first();

        if (!$employee_salary) {
            throw new \Exception("Employee salary not found for employee number: {$this->employee_no}");
        }

        $this->salary_amount = filter_var(
            $employee_salary->amount,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    private function getEmployeeInformation()
    {
        $employee_information = $this->salaryEmployeeService->activeOrg($this->employee_no)
            ->leftJoin('employee_information', 'eo1.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'eo1.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->select(
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

        $this->name = $employee_information->firstname . ' ' . 
            ($employee_information->middlename ? $employee_information->middlename . ' ' : '') . 
            $employee_information->lastname . 
            ($employee_information->suffix ? ' ' . $employee_information->suffix : '');
        $this->position = $employee_information->position_name;
        $this->employment_type = $employee_information->employment_type_id;
        $this->user_id = $employee_information->user_id;
    }
}
