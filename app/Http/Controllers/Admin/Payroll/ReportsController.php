<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Enums\EmploymentTypesEnum;

class ReportsController extends Controller
{
    public function index(Request $request) {

        $employees = DB::table('employee_personal as ep')
            ->select(
                'ep.firstname',
                'ep.lastname',
                'ep.employee_no',
                'u.name as unit_name'
            )
            ->leftJoin(DB::raw('(SELECT employee_no, MAX(id) AS max_id 
                                FROM employee_organization 
                                GROUP BY employee_no) ec_max'),
                    'ep.employee_no', '=', 'ec_max.employee_no')
            ->leftJoin('employee_organization as ec', 'ec.id', '=', 'ec_max.max_id')
            ->leftJoin('units as u', 'ec.unit_id', '=', 'u.id')
            ->orderBy('u.name')
            ->get()
            ->groupBy('unit_name');

        $payroll_registry = DB::table('payroll_salary')
                ->get();

        return view('admin.pages.reports.index', compact('employees', 'payroll_registry'));

    }
}
