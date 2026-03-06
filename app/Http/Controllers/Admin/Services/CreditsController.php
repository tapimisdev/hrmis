<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;

class CreditsController extends Controller
{
    
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index()
    {

        $employees = $this->employeeService->getEmployees('active', null, null, null);

        $employees = $employees->map(function ($emp) {
                        return [
                            'employee_no' => $emp->employee_no,
                            'firstname' => $emp->firstname,
                            'lastname' => $emp->lastname
                        ];
                    });

        $data = [];

        foreach($employees as $employee) {
            $data[$employee['employee_no']] = [
                'firstname' => $employee['firstname'],
                'lastname'  => $employee['lastname'],
                'sl' => $this->employeeService->checkLeaveCredits($employee['employee_no'], 1)->balance ?? 0,
                'vl' => $this->employeeService->checkLeaveCredits($employee['employee_no'], 2)->balance ?? 0,
                'spl' => $this->employeeService->checkLeaveCredits($employee['employee_no'], 7)->balance ?? 0,
                'wl' => $this->employeeService->checkLeaveCredits($employee['employee_no'], 15)->balance ?? 0,
                'offset' => $this->employeeService->checkOffsetCredits($employee['employee_no'])->balance ?? 0,
            ];
        }
        
        return view('admin.pages.services.credits.index', compact('data'));
    }
}
