<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryPay\CreateRequest;
use App\Services\Exports\AUTService;
use App\Services\Exports\PayslipService;
use App\Services\Exports\RegistryService;
use App\Services\SalaryPay\AutDeductionService;
use App\Services\SalaryPay\GetEmployeeService;
use App\Services\SalaryPay\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryApiController extends Controller
{
    protected $salary_payroll_service;
    protected $autDeductionService;

    public function __construct(
        PayrollService $salary_payroll_service,
        AutDeductionService $autDeductionService
    ) {
        $this->salary_payroll_service = $salary_payroll_service;
        $this->autDeductionService = $autDeductionService;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'employment_type' => 'nullable|integer',
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'month' => 'required|integer|min:1|max:12',
            'cutoff' => 'nullable|string|max:50',
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

        return response()->json($holidays->merge($suspensions));
    }

    public function getPayrollData(string $payroll_id, bool $isGrouped = true)
    {
        $employee_salary = new GetEmployeeService($payroll_id, $isGrouped);
        $employee_salary->getAndMapEmployeeSalary();

        return response()->json($employee_salary->employees);
    }

    public function downloadPayrollRegistry($payroll_no)
    {
        return app(RegistryService::class)->download($payroll_no);
    }

    public function downloadAbsencesLeaves($payroll_no)
    {
        return app(AUTService::class)->download($payroll_no);
    }

    public function downloadPayslip($payroll_no)
    {
        return app(PayslipService::class)->download($payroll_no);
    }

    public function previewAutDeductions(string $payroll_id)
    {
        $payroll = $this->autDeductionService->getRegularPayroll($payroll_id);

        return response()->json([
            'data' => $this->autDeductionService->buildAutDeductionRows($payroll)->values(),
            'meta' => [
                'payroll_id' => $payroll->id,
                'payroll_no' => $payroll->payroll_no,
                'as_of' => Carbon::parse($payroll->payroll_date)->format('Y-m'),
                'status' => $payroll->status,
            ],
        ]);
    }

    public function applyAutDeductions(string $payroll_id)
    {
        $payroll = $this->autDeductionService->getRegularPayroll($payroll_id);
        $validated = request()->validate([
            'rows' => 'nullable|array',
            'rows.*.pspe_id' => 'required_with:rows|integer',
            'rows.*.equivalent' => 'required_with:rows|numeric|min:0',
        ]);

        $savedCount = $this->autDeductionService->saveAutDeductions(
            $payroll,
            $validated['rows'] ?? []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'AUT deductions saved successfully.',
            'saved_count' => $savedCount,
        ]);
    }

    private function getEmployeePayslip($payroll_id)
    {
        return DB::table('payroll_salary_employee as pse')
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
    }
}
