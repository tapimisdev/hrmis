<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EmployeeService;
use App\Exports\OffsetCreditsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SalaryHistoryController extends Controller
{

    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function index(Request $request, ? string $employee_no = null) {

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $salaryHistory = $this->employeeService->getSalaryHistory($employee_no);
        $latestSalary = $salaryHistory->last();

        $data = [
            'history' => $salaryHistory,
            'latestSalary' => $latestSalary,
        ];

        return view('admin.pages.hris.salary-history', compact('isExists', 'employee_no', 'data'));
        
    }

}
