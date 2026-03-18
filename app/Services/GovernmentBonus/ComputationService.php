<?php

namespace App\Services\GovernmentBonus;

use App\Services\SalaryEmloyeeService;
use Illuminate\Support\Facades\DB;

class ComputationService
{
    protected $salaryEmployeeService;
    protected $employee_no;
    protected $payroll_id;
    protected $government_bonus_type_id;
    protected $name;
    protected $position;
    protected $payroll_date;
    protected $computation_type;
    protected $computation_value;

    public function __construct(SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    public function process($employeeNo, $payrollId)
    {
        $this->employee_no = $employeeNo;
        $this->payroll_id = $payrollId;

        $this->getPayrollDetails();
        $this->getEmployeeInformation();

        $bonusAmount = $this->getBonusAmount();
        $total = $bonusAmount;
        $netPay = $total;

        DB::table('payroll_government_bonus_employee')->insert([
            'payroll_government_bonus_id' => $this->payroll_id,
            'government_bonus_type_id' => $this->government_bonus_type_id,
            'employee_no' => $this->employee_no,
            'name' => $this->name,
            'position' => $this->position,
            'bonus_amount' => $bonusAmount,
            'total' => $total,
            'adjustments' => 0,
            'net_pay' => $netPay,
            'remarks' => null,
        ]);

        return [
            'bonus_amount' => $bonusAmount,
            'net_pay' => $netPay,
        ];
    }

    private function getPayrollDetails(): void
    {
        $payroll = DB::table('payroll_government_bonus as pgb')
            ->leftJoin('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
            ->where('pgb.id', $this->payroll_id)
            ->select('pgb.month', 'pgb.government_bonus_type_id', 'gbt.computation_type', 'gbt.computation_value')
            ->first();

        if (!$payroll) {
            throw new \Exception("Payroll not found for ID: {$this->payroll_id}");
        }

        $this->payroll_date = $payroll->month;
        $this->government_bonus_type_id = $payroll->government_bonus_type_id;
        $this->computation_type = $payroll->computation_type;
        $this->computation_value = $payroll->computation_value;
    }

    private function getBonusAmount(): float
    {
        if ($this->computation_type === 'fixed') {
            return round((float) ($this->computation_value ?? 0), 2);
        }

        if ($this->computation_type === 'percentage') {
            $salary = $this->getEmployeeSalaryAmount();
            $percentage = (float) ($this->computation_value ?? 0);
            return round($salary * ($percentage / 100), 2);
        }

        return 0.00;
    }

    private function getEmployeeSalaryAmount(): float
    {
        $salary = $this->salaryEmployeeService
            ->activeSalary($this->employee_no, date('Y-m-t', strtotime($this->payroll_date . '-01')))
            ->select('es1.amount')
            ->first();

        if (!$salary) {
            return 0.00;
        }

        return (float) filter_var($salary->amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    private function getEmployeeInformation(): void
    {
        $employeeInformation = $this->salaryEmployeeService->activeOrg($this->employee_no)
            ->leftJoin('employee_information', 'eo1.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'eo1.position_id', '=', 'positions.id')
            ->select(
                'employee_personal.firstname',
                'employee_personal.middlename',
                'employee_personal.lastname',
                'employee_personal.suffix',
                'positions.name as position_name'
            )
            ->first();

        if (!$employeeInformation) {
            throw new \Exception("Employee information not found for employee number: {$this->employee_no}");
        }

        $this->name = trim(implode(' ', array_filter([
            $employeeInformation->firstname,
            $employeeInformation->middlename,
            $employeeInformation->lastname,
            $employeeInformation->suffix,
        ])));
        $this->position = $employeeInformation->position_name;
    }
}
