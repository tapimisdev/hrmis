<?php

namespace App\Services\PeraRata;
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
        $payroll = DB::table('payroll_pera_rata')
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

        $employees = DB::table('payroll_pera_rata_employee')
            ->where('payroll_pera_rata_id', $this->payroll_id)
            ->get();

        $data = $employees->map(function ($e) {
            return [
                'employee_no'    => $e->employee_no,
                'name'           => strtoupper($e->name),
                'position'       => $e->position,
                'pera'   => $e->pera,
                'representation_allowance'    => $e->representation_allowance,
                'transportion_allowance'     => $e->transportion_allowance,
                'absences' => $e->absences,
                'ut_deductions' => $e->ut_deductions,
                'total' => $e->total,
                'healthcard'     => $e->healthcard,
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
