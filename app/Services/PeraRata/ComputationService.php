<?php

namespace App\Services\PeraRata;

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

    private function getPeraRata(string $employee_no)
    {
        [$year, $month] = explode('-', $this->payroll_date);
        $year = (int) $year;
        $month = (int) $month;

        $componentTypes = [
            TableSettingsEnum::PERA->value,
            TableSettingsEnum::RATA->value,
            'representation_allowance',
            'transportation_allowance',
        ];

        // type => payroll_components_years.id
        $componentYearIdByType = DB::table('payroll_components_years as pcy')
            ->join('payroll_components_settings as pcs', function ($join) {
                $join->on('pcs.table_id', '=', 'pcy.payroll_component_id');
            })
            ->whereIn('pcs.type', $componentTypes)
            ->where('pcy.year', $year)
            ->pluck('pcy.id', 'pcs.type');

        $yearIds = $componentYearIdByType->values();

        $amountsByYearId = DB::table('employee_payroll_components')
            ->whereIn('tax_deduction_id', $yearIds)
            ->where('employee_no', $employee_no)
            ->where('month', $month)
            ->pluck('amount', 'tax_deduction_id');

        $peraYearId = $componentYearIdByType[TableSettingsEnum::PERA->value] ?? null;
        $rataYearId = $componentYearIdByType[TableSettingsEnum::RATA->value] ?? null;
        $representationYearId = $componentYearIdByType['representation_allowance'] ?? null;
        $transportationYearId = $componentYearIdByType['transportation_allowance'] ?? null;

        $rataTotal = $rataYearId ? (float) ($amountsByYearId[$rataYearId] ?? 0) : 0;
        $representation = $representationYearId ? (float) ($amountsByYearId[$representationYearId] ?? 0) : 0;
        $transportation = $transportationYearId ? (float) ($amountsByYearId[$transportationYearId] ?? 0) : 0;

        if ($representation === 0.0 && $transportation === 0.0 && $rataTotal > 0) {
            $representation = $rataTotal / 2;
            $transportation = $rataTotal / 2;
        }

        if ($rataTotal === 0.0 && ($representation > 0 || $transportation > 0)) {
            $rataTotal = $representation + $transportation;
        }

        return [
            'pera_employee' => $peraYearId ? (float) ($amountsByYearId[$peraYearId] ?? 0) : 0,
            'rata_employee' => $rataTotal,
            'representation_allowance' => $representation,
            'transportation_allowance' => $transportation,
        ];
    }

    public function process($employee_no, $payroll_id) 
    {
        $this->employee_no = $employee_no;
        $this->payroll_id = $payroll_id;

        $this->getPayrollDetails();
        $this->getEmployeeSalaryDetails();
        $this->getEmployeeInformation();

        $pera_rata = $this->getPeraRata($employee_no);

        $pera = (float) $pera_rata['pera_employee'];
        $rata_total = (float) $pera_rata['rata_employee'];
        $representationAllowance = (float) $pera_rata['representation_allowance'];
        $transportationAllowance = (float) $pera_rata['transportation_allowance'];


        $payload = [
            'user_id' => $this->user_id,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date
        ];

        $dtr = $this->daily_time_record_service->getDTR($payload);
        $dtr_summary = $dtr['payroll_value'];
        $actual_presence  = $dtr_summary['actual_presence'];
        $total_ut = $dtr_summary['late_undertime'] ?? 0;

        # to be get
        $absences = 0;
        $less_healthcard = 0;

        $ut_deductions = $this->computeUTDeduction($total_ut);

        $gross = $pera + $representationAllowance + $transportationAllowance;
        $deductions = $absences + $ut_deductions;

        $total = max($gross - $deductions, 0);

        $netPay = $total - $less_healthcard;
        
        DB::table('payroll_pera_rata_employee')
            ->insert([
                'payroll_pera_rata_id' => $this->payroll_id,
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'position' => $this->position,
                'pera' => $pera,
                'representation_allowance' => $representationAllowance,
                'transportion_allowance' => $transportationAllowance,
                'absences' => $absences,
                'ut_deductions' => $ut_deductions,
                'total' => $total,
                'healthcard' => $less_healthcard,
                'adjustments' => 0,
                'net_pay' => $netPay,
                'remarks' => null,
            ]);

        return [
            'net_pay' => $netPay,
        ];
    }

    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_pera_rata')
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
