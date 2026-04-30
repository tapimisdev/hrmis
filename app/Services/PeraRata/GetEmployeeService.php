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

        $latestOrgDate = DB::table('employee_organization')
            ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
            ->whereDate('effectivity_date', '<=', Carbon::parse($payroll->month)->endOfMonth()->toDateString())
            ->groupBy('employee_no');

        $latestOrgId = DB::table('employee_organization')
            ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
            ->groupBy('employee_no', 'effectivity_date');

        $employees = DB::table('payroll_pera_rata_employee as ppre')
            ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                $join->on('ppre.employee_no', '=', 'latest_org_date.employee_no');
            })
            ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                    ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
            })
            ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('ppre.payroll_pera_rata_id', $this->payroll_id)
            ->select(
                'ppre.*',
                'd.id as division_id',
                'd.name as division_name',
                'd.code as division_code'
            )
            ->orderBy('d.name')
            ->orderBy('ppre.employee_no')
            ->get();

        $data = $employees->map(function ($e) {
            return [
                'id'             => $e->id,
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
                'division_id'     => $e->division_id,
                'division_name'   => $e->division_name ?? 'No Division',
                'division_code'   => $e->division_code,
            ];
        })->toArray();

        $this->employees = [
            'month_year' => Carbon::parse($payroll->month)->format('F Y'),
            'employees' => $data,
        ];

        return $this->employees;
    }
}
