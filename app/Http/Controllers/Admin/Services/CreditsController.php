<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EmployeeService;
use Yajra\DataTables\Facades\DataTables;

class CreditsController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getData();
        }

        return view('admin.pages.services.credits.index');
    }

    public function getData()
    {
        $employees = $this->employeeService->getEmployees('active', null, null, null);

        $employees = $employees->map(function ($emp) {

            return [
                'employee_no' => $emp->employee_no,
                'name' => $emp->firstname . ' ' . $emp->lastname,
                'vl' => $this->employeeService->checkLeaveCredits($emp->employee_no, 2)->balance ?? 0,
                'sl' => $this->employeeService->checkLeaveCredits($emp->employee_no, 1)->balance ?? 0,
                'wl' => $this->employeeService->checkLeaveCredits($emp->employee_no, 15)->balance ?? 0,
                'offset' => $this->employeeService->checkOffsetCredits($emp->employee_no)->balance ?? 0,
            ];
        });

        return DataTables::of($employees)->make(true);
    }
}