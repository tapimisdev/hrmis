<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollGroupEmployeesController extends Controller
{
    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
        // $this->middleware('permission:hr.hris.view')->only('index');
        // $this->middleware('permission:hr.hris.delete')->only(['remove', 'restore']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $group = DB::table('payroll_groups')
            ->where('payroll_groups.is_active', true)
            ->where('payroll_groups.id', $id)
            ->leftJoin('employment_types', 'payroll_groups.employment_type_id', '=', 'employment_types.id')
            ->leftJoin('payroll_group_employees', 'payroll_groups.id', '=', 'payroll_group_employees.payroll_group_id')
            ->select(
                'payroll_groups.id',
                'payroll_groups.name',
                'payroll_groups.employment_type_id',
                'employment_types.name as employment_type_name',
                'payroll_groups.remarks',
                DB::raw('COUNT(payroll_group_employees.employee_no) as employee_count')
            )
            ->groupBy(
                'payroll_groups.id',
                'payroll_groups.name',
                'employment_types.name',
                'payroll_groups.remarks'
            )
            ->first();

        if (!$group) abort(404);

        $employment_type = $group->employment_type_id ?? null; // if you need it, include in select below

        $employees = $this->employeeService->getEmployees(null, null, null, $group->employment_type_id);

        $selected = DB::table('payroll_group_employees')
            ->where('payroll_group_id', $id)
            ->pluck('employee_no')
            ->toArray();

        return view('admin.pages.payroll.payroll-group.employees.index', compact('group', 'employees', 'selected'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request, $id)
    {
        $group = DB::table('payroll_groups')->where('id', $id)->first();
        if (!$group) abort(404);

        $employeeNos = $request->input('employees', []);
        if (!is_array($employeeNos)) $employeeNos = [];

        DB::transaction(function () use ($id, $employeeNos) {
            DB::table('payroll_group_employees')->where('payroll_group_id', $id)->delete();

            if (count($employeeNos)) {
                $rows = array_map(fn($empNo) => [
                    'payroll_group_id' => $id,
                    'employee_no' => $empNo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $employeeNos);

                DB::table('payroll_group_employees')->insert($rows);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Employees saved successfully.',
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
