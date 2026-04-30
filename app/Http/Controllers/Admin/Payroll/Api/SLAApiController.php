<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Admin\Payroll\Api\Concerns\PreparesPayrollExports;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SLAPay\CreateRequest;
use App\Services\SLAPay\PayrollService;
use App\Services\SLAPay\GetEmployeeService;
use App\Services\Exports\PayslipService;
use App\Services\Exports\AUTService;
use App\Services\Exports\SLARegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SLAApiController extends Controller
{
    use PreparesPayrollExports;

    protected $sla_pay_service;

    public function __construct(PayrollService $sla_pay_service)
    {
        $this->sla_pay_service = $sla_pay_service;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'employment_type' => 'nullable|integer',
            'month' => 'required',
            'status' => 'nullable|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->sla_pay_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }

    public function validateAndGetEmployee(CreateRequest $request)
    {
        $validatedData = $request->validated();

        $employees = $this->sla_pay_service->getEligibleEmployees($validatedData);

        return response(['data' => $employees, 'success'], 200);
    }

    public function getAdjustments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $holidays = $this->sla_pay_service->getHolidays($validated);
        $suspensions = $this->sla_pay_service->getSuspensions($validated);

        $events = $holidays->merge($suspensions);

        return response()->json($events);
    }

    public function approvers()
    {
        $approver_id = DB::table('application_approver')
                        ->where('type', 'payroll')
                        ->value('id');

        $user_approvers = DB::table('application_approver_users as au')
                            ->leftJoin('employee_information as ei', 'au.user_id', '=', 'ei.user_id')
                            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                            ->where('au.application_approver_id', $approver_id)
                            ->select('ei.employee_no', 'au.level', 'ep.firstname', 'ep.lastname', 'ep.middlename', 'ei.user_id')
                            ->get();

        return response()->json($user_approvers);
    }

    public function downloadPayrollRegistry($payroll_no)
    {
       $this->preparePayrollExport();

       return app(SLARegistryService::class)->download($payroll_no);
    }

    public function downloadAbsencesLeaves($payroll_no) {
        $this->preparePayrollExport();

        return app(AUTService::class)->download($payroll_no);
    }
    
    public function downloadPayslip($payroll_no)
    {
        $this->preparePayrollExport();

        return app(PayslipService::class)->download($payroll_no);
    }

    private function getEmployeePayslip($payroll_id)
    {
        $employees = DB::table('payroll_sla_pay_employee as pse')
            ->where('pse.payroll_sla_pay_id', $payroll_id)
            ->leftJoinSub(
                DB::table('employee_projects as ep')
                    ->select('ep.*')
                    ->whereRaw('ep.id IN (SELECT MAX(id) FROM employee_projects GROUP BY employee_no)'),
                'latest_proj',
                'pse.employee_no',
                '=',
                'latest_proj.employee_no'
            )
            ->leftJoin('projects as p', 'latest_proj.project_id', '=', 'p.id')
            ->select(
                'pse.*',
                'latest_proj.*',
                'p.name as project_name'
            )
            ->get();

        return $employees;
    }

    public function getPayrollData(string $payroll_id, bool $isGrouped = true) {
        $employee_salary = new GetEmployeeService($payroll_id);
        $employee_salary->getAndMapEmployeeSalary();
        $employees = $employee_salary->employees;
        return response()->json($employees);
    }

}
