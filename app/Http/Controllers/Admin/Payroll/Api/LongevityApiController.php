<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LongevityPay\CreateRequest;
use App\Services\LongevityPay\PayrollService;
use App\Services\LongevityPay\GetEmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LongevityApiController extends Controller
{
    protected $payroll_service;

    public function __construct(PayrollService $payroll_service)
    {
        $this->payroll_service = $payroll_service;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'employment_type' => 'nullable|integer',
            'month' => 'required',
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

    public function getPayrollData(string $payroll_id, bool $isGrouped = true)
    {
        $employee_salary = new GetEmployeeService($payroll_id);
        $employee_salary->getAndMapEmployeeSalary();
        $employees = $employee_salary->employees;
        return response()->json($employees);
    }
}
