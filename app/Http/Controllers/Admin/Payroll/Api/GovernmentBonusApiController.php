<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GovernmentBonus\CreateRequest;
use App\Services\GovernmentBonus\GetEmployeeService;
use App\Services\GovernmentBonus\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GovernmentBonusApiController extends Controller
{
    protected $payroll_service;

    public function __construct(PayrollService $payrollService)
    {
        $this->payroll_service = $payrollService;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'employment_type' => 'nullable|integer',
            'government_bonus_type_id' => 'nullable|integer',
            'year' => 'required|digits:4',
            'month' => 'nullable',
            'status' => 'nullable|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->payroll_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }

    public function validateAndGetEmployee(CreateRequest $request)
    {
        $validatedData = $request->validated();
        $employees = $this->payroll_service->getEligibleEmployees($validatedData);

        return response(['data' => $employees, 'success'], 200);
    }

    public function approvers()
    {
        $approverId = DB::table('application_approver')
            ->where('type', 'payroll')
            ->value('id');

        $userApprovers = DB::table('application_approver_users as au')
            ->leftJoin('employee_information as ei', 'au.user_id', '=', 'ei.user_id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('au.application_approver_id', $approverId)
            ->select('ei.employee_no', 'au.level', 'ep.firstname', 'ep.lastname', 'ep.middlename', 'ei.user_id')
            ->get();

        return response()->json($userApprovers);
    }

    public function getPayrollData(string $payrollId)
    {
        $employeeSalary = new GetEmployeeService($payrollId);
        $employeeSalary->getAndMapEmployeeSalary();

        return response()->json($employeeSalary->employees);
    }

    public function bonusTypes()
    {
        return response()->json([
            'data' => $this->payroll_service->getActiveBonusTypes(),
        ]);
    }
}
