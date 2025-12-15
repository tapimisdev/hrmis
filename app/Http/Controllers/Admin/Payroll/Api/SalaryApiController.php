<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryPay\CreateRequest;
use App\Services\Exports\PayslipService;
use App\Services\Exports\AUTService;
use App\Services\Exports\RegistryService;
use App\Services\SalaryPay\GetEmployeeService;
use App\Services\SalaryPay\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryApiController extends Controller
{

    protected $salary_payroll_service;

    public function __construct(PayrollService $salary_payroll_service)
    {
        $this->salary_payroll_service = $salary_payroll_service;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'month' => 'required|integer|min:1|max:12',
            'cutoff' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->salary_payroll_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }

    public function validateAndGetEmployee(CreateRequest $request)
    {
        $validatedData = $request->validated();

        $employees = $this->salary_payroll_service->getEligibleEmployees($validatedData);

        return response(['data' => $employees, 'success'], 200);
    }

    public function getAdjustments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $holidays = $this->salary_payroll_service->getHolidays($validated);
        $suspensions = $this->salary_payroll_service->getSuspensions($validated);

        $events = $holidays->merge($suspensions);

        return response()->json($events);
    }

    public function getPayrollData(string $payroll_id, bool $isGrouped = true) 
    {
        $employee_salary = new GetEmployeeService($payroll_id, $isGrouped);
        $employee_salary->getAndMapEmployeeSalary();
        $employees = $employee_salary->employees;
        
        return response()->json($employees);
    }

    public function downloadPayrollRegistry($payroll_no)
    {
       return app(RegistryService::class)->download($payroll_no);
    }

    public function downloadAbsencesLeaves($payroll_no) {
        return app(AUTService::class)->download($payroll_no);
    }
    
    public function downloadPayslip($payroll_no)
    {
        return app(PayslipService::class)->download($payroll_no);
    }

    private function getEmployeePayslip($payroll_id)
    {
        $employees = DB::table('payroll_salary_employee as pse')
            ->where('pse.payroll_salary_id', $payroll_id)
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











}
