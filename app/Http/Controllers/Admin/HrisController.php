<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HrisController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if (request()->ajax()) {

            $query = $this->employeeService->getEmployee();

            return $this->datatable($query);
        }

        return view('admin.pages.hris.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.hris.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
                <a href="' . route('hris.employee.edit', $row->id) . '" 
                class="btn btn-outline-secondary btn ms-1" 
                title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
