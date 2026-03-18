<?php

namespace App\Services\LongevityPay;

use App\Enums\TableSettingsEnum;
use App\Services\SalaryEmloyeeService;
use Illuminate\Support\Facades\DB;

class ComputationService
{
    protected $salaryEmployeeService;
    protected $employee_no;
    protected $payroll_id;
    protected $user_id;
    protected $name;
    protected $position;
    protected $payroll_date;

    public function __construct(SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    public function process($employee_no, $payroll_id)
    {
        $this->employee_no = $employee_no;
        $this->payroll_id = $payroll_id;

        $this->getPayrollDetails();
        $this->getEmployeeInformation();

        $longevityAmount = $this->getLongevityAmount();
        $total = $longevityAmount;
        $netPay = $total;

        DB::table('payroll_longevity_pay_employee')->insert([
            'payroll_longevity_pay_id' => $this->payroll_id,
            'employee_no' => $this->employee_no,
            'name' => $this->name,
            'position' => $this->position,
            'longevity_amount' => $longevityAmount,
            'total' => $total,
            'adjustments' => 0,
            'net_pay' => $netPay,
            'remarks' => null,
        ]);

        return [
            'longevity_amount' => $longevityAmount,
            'net_pay' => $netPay,
        ];
    }

    private function getPayrollDetails()
    {
        $payroll = DB::table('payroll_longevity_pay')
            ->where('id', $this->payroll_id)
            ->first();

        if (!$payroll) {
            throw new \Exception("Payroll not found for ID: {$this->payroll_id}");
        }

        $this->payroll_date = $payroll->month;
    }

    private function getLongevityAmount()
    {
        [$year, $month] = explode('-', $this->payroll_date);

        $componentTableId = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::LONGETIVITY->value)
            ->value('table_id');

        if (!$componentTableId) {
            throw new \Exception('Longevity payroll component setting is not configured.');
        }

        $componentYearId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentTableId)
            ->where('year', (int) $year)
            ->value('id');

        if (!$componentYearId) {
            throw new \Exception("Longevity payroll component year is not configured for {$year}.");
        }

        $amount = DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $componentYearId)
            ->where('employee_no', $this->employee_no)
            ->where('month', (int) $month)
            ->value('amount');

        if (is_null($amount)) {
            throw new \Exception("Longevity amount not found for employee {$this->employee_no}.");
        }

        return (float) $amount;
    }

    private function getEmployeeInformation()
    {
        $employeeInformation = $this->salaryEmployeeService->activeOrg($this->employee_no)
            ->leftJoin('employee_information', 'eo1.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'eo1.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->select(
                'employee_personal.firstname',
                'employee_personal.middlename',
                'employee_personal.lastname',
                'employee_personal.suffix',
                'positions.name as position_name',
                'users.id as user_id'
            )
            ->first();

        if (!$employeeInformation) {
            throw new \Exception("Employee information not found for employee number: {$this->employee_no}");
        }

        $this->name = $employeeInformation->firstname . ' ' .
            ($employeeInformation->middlename ? $employeeInformation->middlename . ' ' : '') .
            $employeeInformation->lastname .
            ($employeeInformation->suffix ? ' ' . $employeeInformation->suffix : '');
        $this->position = $employeeInformation->position_name;
        $this->user_id = $employeeInformation->user_id;
    }
}
