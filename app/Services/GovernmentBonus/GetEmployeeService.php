<?php

namespace App\Services\GovernmentBonus;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetEmployeeService
{
    protected $payroll_id;
    public $employees = [];

    public function __construct($payrollId)
    {
        $this->payroll_id = $payrollId;
    }

    public function getAndMapEmployeeSalary()
    {
        $payroll = DB::table('payroll_government_bonus as pgb')
            ->leftJoin('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
            ->where('pgb.id', $this->payroll_id)
            ->select(
                'pgb.id',
                'pgb.month',
                'pgb.employment_type_id',
                'gbt.name as bonus_type_name',
                'gbt.computation_type'
            )
            ->first();

        if (!$payroll) {
            $this->employees = [
                'month_year' => '',
                'bonus_type_name' => '',
                'computation_type' => '',
                'employees' => [],
            ];
            return $this->employees;
        }

        $employees = DB::table('payroll_government_bonus_employee')
            ->where('payroll_government_bonus_id', $this->payroll_id)
            ->get();

        $data = $employees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name' => strtoupper($employee->name),
                'position' => $employee->position,
                'bonus_amount' => $employee->bonus_amount,
                'total' => $employee->total,
                'adjustments' => $employee->adjustments,
                'net_pay' => $employee->net_pay,
                'remarks' => $employee->remarks,
            ];
        })->toArray();

        $this->employees = [
            'month_year' => Carbon::parse($payroll->month)->format('F Y'),
            'bonus_type_name' => $payroll->bonus_type_name,
            'computation_type' => $payroll->computation_type,
            'employees' => $data,
        ];

        return $this->employees;
    }
}
