<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
    }

    public function transfer(Request $request) {

        $selectedEmployee = $request->employee_no;

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        $employees = $this->employeeService->getEmployees(null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        return view('admin.pages.hris.transfer', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'selectedEmployee'
        ));
    }

    public function update_salary(Request $request) {

        $selectedEmployee = $request->employee_no;

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        $employees = $this->employeeService->getEmployees(null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        return view('admin.pages.hris.salary', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'selectedEmployee'
        ));
    }
}