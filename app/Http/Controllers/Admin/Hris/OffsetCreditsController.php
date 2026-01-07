<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EmployeeService;
use App\Services\GenerateService;

class OffsetCreditsController extends Controller
{

    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function index(Request $request, ? string $employee_no = null) {
        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $leaves = $this->employeeService->getLeaveTypes($employee_no);

        return view('admin.pages.hris.offset-credits', compact('isEdit', 'id', 'employee_no', 'isExists', 'leaves'));
    }

    public function save(string $employee_no, Request $request) {

    }
}
