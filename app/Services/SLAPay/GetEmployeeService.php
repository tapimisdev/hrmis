<?php

namespace App\Services\SLAPay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetEmployeeService
{
    protected $payroll_id;
    public $employees = [];

    public function __construct($payroll_id)
    {
        $this->payroll_id = $payroll_id;
    }

    public function getAndMapEmployeeSalary()
    {
        $payroll = DB::table('payroll_sla_pay')
            ->where('id', $this->payroll_id)
            ->select('id', 'month', 'employment_type_id')
            ->first();

        if (!$payroll) {
            $this->employees = [
                'month_year' => '',
                'employees' => [],
            ];
            return $this->employees;
        }

        $employees = DB::table('payroll_sla_pay_employee')
            ->where('payroll_sla_pay_id', $this->payroll_id)
            ->get();

        $data = $employees->map(function ($e) {
            return [
                'employee_no'    => $e->employee_no,
                'name'           => strtoupper($e->name),
                'position'       => $e->position,
                'subsistence_allowance'   => $e->subsistence_allowance,
                'laundry_allowance'    => $e->laundry_allowance,
                'total_sla'     => $e->total_sla,
                'ut_deductions' => $e->ut_deductions,
                'uniform_deduction'     => $e->uniform_deduction,
                'total'    => $e->total,
                'healthcard' => $e->healthcard,
                'adjustments'    => $e->adjustments,
                'net_pay'        => $e->net_pay,
                'remarks'        => $e->remarks,
            ];
        })->toArray();

        $this->employees = [
            'month_year' => Carbon::parse($payroll->month)->format('F Y'),
            'employees' => $data,
        ];

        return $this->employees;
    }
}
