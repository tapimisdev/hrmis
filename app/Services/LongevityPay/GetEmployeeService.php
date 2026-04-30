<?php

namespace App\Services\LongevityPay;

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
        $payroll = DB::table('payroll_longevity_pay')
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

        $organizationEffectiveDate = Carbon::parse($payroll->month)->endOfMonth()->toDateString();

        $latestOrganizations = DB::table('employee_organization as eo1')
            ->select('eo1.employee_no', 'eo1.division_id')
            ->where(
                'eo1.id',
                DB::table('employee_organization as eo2')
                    ->select('eo2.id')
                    ->whereColumn('eo2.employee_no', 'eo1.employee_no')
                    ->where('eo2.created_at', '<=', $organizationEffectiveDate)
                    ->orderByDesc('eo2.created_at')
                    ->orderByDesc('eo2.id')
                    ->limit(1)
            );

        $employees = DB::table('payroll_longevity_pay_employee as plpe')
            ->leftJoinSub($latestOrganizations, 'latest_org', function ($join) {
                $join->on('latest_org.employee_no', '=', 'plpe.employee_no');
            })
            ->leftJoin('divisions as d', 'latest_org.division_id', '=', 'd.id')
            ->where('plpe.payroll_longevity_pay_id', $this->payroll_id)
            ->select(
                'plpe.*',
                'latest_org.division_id',
                'd.name as division_name',
                'd.code as division_code'
            )
            ->orderBy('d.name')
            ->orderBy('plpe.employee_no')
            ->get();

        $data = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'employee_no' => $e->employee_no,
                'name' => strtoupper($e->name),
                'position' => $e->position,
                'division_id' => $e->division_id,
                'division_name' => $e->division_name ?? 'No Division',
                'division_code' => $e->division_code,
                'longevity_amount' => $e->longevity_amount,
                'w_tax' => $e->w_tax ?? 0,
                'total' => $e->total,
                'adjustments' => $e->adjustments,
                'net_pay' => $e->net_pay,
                'remarks' => $e->remarks,
            ];
        })->toArray();

        $this->employees = [
            'month_year' => Carbon::parse($payroll->month)->format('F Y'),
            'employees' => $data,
        ];

        return $this->employees;
    }
}
