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
    protected $formula_expression;
    protected $service_date_basis;
    protected $date_hired_company;
    protected $date_hired_organization;

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
            ->select(
                'pgb.month',
                'pgb.government_bonus_type_id',
                'gbt.computation_type',
                'gbt.computation_value',
                'gbt.formula_expression',
                'gbt.service_date_basis'
            )
            ->first();

        if (!$payroll) {
            throw new \Exception("Payroll not found for ID: {$this->payroll_id}");
        }

        $this->payroll_date = $payroll->month;
        $this->government_bonus_type_id = $payroll->government_bonus_type_id;
        $this->computation_type = $payroll->computation_type;
        $this->computation_value = $payroll->computation_value;
        $this->formula_expression = $payroll->formula_expression;
        $this->service_date_basis = $payroll->service_date_basis;
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

        if ($this->computation_type === 'formula') {
            $salary = $this->getEmployeeSalaryAmount();

            return $this->evaluateFormula((string) $this->formula_expression, [
                'salary' => $salary,
                'basic_salary' => $salary,
                'monthly_salary' => $salary,
                'years_of_service' => $this->getYearsOfService(),
                'months_of_service' => $this->getMonthsOfService(),
            ]);
        }

        return 0.00;
    }

    private function evaluateFormula(string $formula, array $variables): float
    {
        $expression = strtolower(trim($formula));

        if ($expression === '') {
            return 0.00;
        }

        uksort($variables, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($variables as $name => $value) {
            $expression = preg_replace(
                '/\b' . preg_quote(strtolower($name), '/') . '\b/',
                (string) round((float) $value, 8),
                $expression
            );
        }

        if (preg_match('/[a-z_]/', $expression)) {
            throw new \Exception('Formula contains unknown variables. Allowed variables: salary, basic_salary, monthly_salary, years_of_service.');
        }

        if (!preg_match('/^[0-9+\-*\/().\s<>=!?:&|]+$/', $expression)) {
            throw new \Exception('Formula contains invalid characters. Allowed characters are numbers, parentheses, spaces, + - * /, comparison operators, and ternary ? :');
        }

        set_error_handler(function ($severity, $message) {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $result = eval('return ' . $expression . ';');
        } finally {
            restore_error_handler();
        }

        if (!is_numeric($result)) {
            throw new \Exception('Formula did not return a numeric result.');
        }

        return round((float) $result, 2);
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

    private function getYearsOfService(): float
    {
        $serviceDate = $this->service_date_basis === 'company'
            ? $this->date_hired_company
            : $this->date_hired_organization;

        if (!$serviceDate) {
            return 0.00;
        }

        return (float) \Carbon\Carbon::parse($serviceDate)
            ->diffInYears(\Carbon\Carbon::parse($this->payroll_date . '-01')->endOfMonth());
    }

    private function getMonthsOfService(): float
    {
        $serviceDate = $this->service_date_basis === 'company'
            ? $this->date_hired_company
            : $this->date_hired_organization;

        if (!$serviceDate) {
            return 0.00;
        }

        return (float) \Carbon\Carbon::parse($serviceDate)
            ->diffInMonths(\Carbon\Carbon::parse($this->payroll_date . '-01')->endOfMonth());
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
                'positions.name as position_name',
                'employee_information.date_hired_company',
                'employee_information.date_hired_organization'
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
        $this->date_hired_company = $employeeInformation->date_hired_company;
        $this->date_hired_organization = $employeeInformation->date_hired_organization;
    }
}
