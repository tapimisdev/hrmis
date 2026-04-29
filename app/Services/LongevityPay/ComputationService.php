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
        $withholdingTax = $this->getLongevityTax();
        $total = $longevityAmount - $withholdingTax;
        $netPay = $total;

        DB::table('payroll_longevity_pay_employee')->insert([
            'payroll_longevity_pay_id' => $this->payroll_id,
            'employee_no' => $this->employee_no,
            'name' => $this->name,
            'position' => $this->position,
            'longevity_amount' => $longevityAmount,
            'w_tax' => $withholdingTax,
            'total' => $total,
            'adjustments' => 0,
            'net_pay' => $netPay,
            'remarks' => null,
        ]);

        return [
            'longevity_amount' => $longevityAmount,
            'w_tax' => $withholdingTax,
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
            return 0;
        }

        $componentYearId = DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentTableId)
            ->where('year', (int) $year)
            ->value('id');

        if (!$componentYearId) {
            return 0;
        }

        $amount = DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $componentYearId)
            ->where('employee_no', $this->employee_no)
            ->where('month', (int) $month)
            ->value('amount');

        return (float) ($amount ?? 0);
    }

    private function getLongevityTax(): float
    {
        [$year, $month] = explode('-', $this->payroll_date);

        $componentYearId = $this->getComponentYearId('tax_id', 'longetivity-tax', (int) $year);

        if (!$componentYearId) {
            return 0;
        }

        return (float) (DB::table('employee_payroll_components')
            ->where('tax_deduction_id', $componentYearId)
            ->where('employee_no', $this->employee_no)
            ->where('month', (int) $month)
            ->value('amount') ?? 0);
    }

    private function getComponentYearId(string $settingsColumn, string $fallbackSlug, int $year): ?int
    {
        $componentId = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::LONGETIVITY->value)
            ->value($settingsColumn);

        if (!$componentId) {
            $componentId = DB::table('payroll_components')
                ->where('slug', $fallbackSlug)
                ->value('id');
        }

        if (!$componentId) {
            return null;
        }

        return DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentId)
            ->where('year', $year)
            ->value('id');
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
