<?php

namespace App\Services\HazardPay;

use App\Services\DailyTimeRecordService;
use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComputationService {

    protected $daily_time_record_service;

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

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function process($employee_no, $payroll_id) 
    {
        $this->employee_no = $employee_no;
        $this->payroll_id = $payroll_id;

        $this->getPayrollDetails();
        $this->getShiftAndWorkScheduleIds();
        $this->getEmployeeSalaryDetails();
        $this->getEmployeeInformation();

        // Hazard payroll is previous month
        $payload = [
            'user_id'   => $this->user_id,
            'startDate' => Carbon::parse($this->start_date)->format('Y-m-d'),
            'endDate'   => Carbon::parse($this->end_date)->format('Y-m-d'),
        ];

        $dtr = $this->daily_time_record_service->getDTR($payload);
        $total_summary_of_dtr = $dtr['payroll_value'];
        $actual_presence  = $total_summary_of_dtr['actual_presence'];

        $entitlementPercentage = 0; 
        $less_healthcard = 0;

        $withHoldingTax = $this->getWithHoldingTax();

        if ($actual_presence >= 15) {
            $entitlementPercentage = 0.15;
        } elseif ($actual_presence >= 8 && $actual_presence < 15) {
            $entitlementPercentage = 0.12;
        } elseif ($actual_presence >= 1 && $actual_presence <= 7) {
            $entitlementPercentage = 0.10;
        } else {
            $entitlementPercentage = 0;
        }

        $hazardPay = ($this->salary_amount * $entitlementPercentage) / 22 * $actual_presence;
        $total = $hazardPay - $withHoldingTax;

        $netPay = $total - $less_healthcard;

        DB::table('payroll_hazard_pay_employee')
            ->insert([
                'payroll_hazard_pay_id' => $this->payroll_id,
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'position' => $this->position,
                'monthly_rate' => $this->salary_amount,
                'entitlement' => $entitlementPercentage,
                'hazard_pay' => $hazardPay,
                'witholding_tax' => $withHoldingTax,
                'total' => $total,
                'healthcard' => $less_healthcard,
                'adjustments' => 0,
                'net_pay' => $netPay,
                'remarks' => null,
            ]);

        return [
            'hazard_pay' => $hazardPay,
            'witholding_tax' => $withHoldingTax,
            'net_pay' => $netPay,
        ];
    }

    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_hazard_pay')
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

        $schedule = DB::table('employee_shift_work_schedule as esw')
            ->leftJoin('shifts as s', 'esw.shift_id', '=', 's.id')
            ->select(
                'esw.id',
                'esw.shift_id',
                'esw.work_schedule_id',
                'esw.effectivity_date',
                's.working_hours',
            )
            ->where('esw.employee_no', $this->employee_no)
            ->where(function ($query) use ($year, $month) {
                $cutoffDate = Carbon::create($year, $month, 1)->endOfMonth();
                $query->whereDate('esw.effectivity_date', '<=', $cutoffDate);
            })
            ->orderByDesc('esw.effectivity_date')
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

        $employee_salary = DB::table('employee_salary')
            ->where('employee_no', $this->employee_no)
            ->where('effectivity_date', '<=', $cutoff)
            ->orderByDesc('effectivity_date')
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

    private function getWithHoldingTax() 
    {
        $date = explode('-', $this->payroll_date);
        $year = (int) $date[0];
        $month = (int) $date[1];

        $component_table_id = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::HAZARD_PA->value)
            ->value('tax_id');

        $components_year_id = DB::table('payroll_components_years')
            ->where('payroll_component_id', $component_table_id)
            ->where('year', $year)
            ->value('id');

        $tax_table_amount = DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $components_year_id)
            ->where('employee_no', $this->employee_no)
            ->where('month', $month)
            ->value('amount');

        return $tax_table_amount ?? 0;
    }
}