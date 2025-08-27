<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class IndexController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
    }


    public function index()
    {

        if (request()->ajax()) {

            $query = $this->employeeService->getEmployee();

            dd($query);

            return $this->datatable($query);
        }

        return view('admin.pages.hris.index');
    }

    public function destroy(string $id)
    {
        //
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('employee_no', function ($row) {
                return $row->employee_information->employee_no;
            })
            ->editColumn('name', function ($row) {
                return $row->employee_personal->firstname . ' ' . $row->employee_personal->lastname;
            })
            ->editColumn('date_hired', function ($row) {
                return $row->employee_information->date_hired ?? '';
            })
            ->addColumn('actions', function ($row) {
               return '
                <a href="' . route('hris.employee.information', [
                    'employee_no' => $row->employee_no
                ]) . '" 
                class="btn btn-outline-secondary btn ms-1" 
                title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
