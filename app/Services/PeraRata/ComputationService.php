<?php

namespace App\Services\PeraRata;

use App\Services\DailyTimeRecordService;
use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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


    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
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

    private function getPeraRata(string $employee_no) {
        [$year, $month] = explode('-', $this->payroll_date);
        $componentIds = DB::table('payroll_components_settings')
            ->whereIn('type', [TableSettingsEnum::PERA->value, TableSettingsEnum::RATA->value])
            ->pluck('table_id', 'type');

        $amounts = DB::table('employee_payroll_components')
            ->whereIn('tax_deduction_id', $componentIds)
            ->where('employee_no', $employee_no)
            ->where('month', $month)
            ->pluck('amount', 'tax_deduction_id');

        return [
            'pera_employee' => $amounts[$componentIds[TableSettingsEnum::PERA->value]] ?? 0,
            'rata_employee' => $amounts[$componentIds[TableSettingsEnum::RATA->value]] ?? 0,
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
        $rata = $rata_total / 2;


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

        $total = $pera + $rata_total - $absences - $ut_deductions;

        $netPay = $total - $less_healthcard;
        
        DB::table('payroll_pera_rata_employee')
            ->insert([
                'payroll_pera_rata_id' => $this->payroll_id,
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'position' => $this->position,
                'pera' => $pera,
                'representation_allowance' => $rata,
                'transportion_allowance' => $rata,
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

        $employee_salary = DB::table('employee_salary')
            ->where('employee_no', $this->employee_no)
            ->where(function($query) {
                $query->whereYear('effectivity_date', '<=', substr($this->payroll_date, 0, 4))
                    ->whereMonth('effectivity_date', '<=', substr($this->payroll_date, 5, 2));
            })
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
}