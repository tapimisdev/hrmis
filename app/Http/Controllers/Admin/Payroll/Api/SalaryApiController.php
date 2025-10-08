<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
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
            'cutoff' => 'required|string|max:50',
            'status' => 'required|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->salary_payroll_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }
}
