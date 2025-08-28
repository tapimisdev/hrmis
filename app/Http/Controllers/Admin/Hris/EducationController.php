<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
    }

    public function index(Request $request, ? string $employee_no = null)
    {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        return view('admin.pages.hris.education', compact('isExists', 'employee_no'));

    }

}
