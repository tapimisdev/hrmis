<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\Steps\ValidateCreatePayrollRequest;
use App\Services\SalaryPayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryApiController extends Controller
{
    protected $salary_payroll_service;

    public function __construct(SalaryPayrollService $salary_payroll_service)
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

    public function validateAndGetEmployee(ValidateCreatePayrollRequest $request)
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

        // dd($user_approvers);
        return response()->json($user_approvers);
    }
}
