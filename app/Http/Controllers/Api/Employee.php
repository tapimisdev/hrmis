<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class Employee extends Controller
{
    
    public function children(Request $request) {

        $employee_no = $request->employee_no;

        $query = DB::table('employee_children')
            ->where('employee_no', $employee_no)
            ->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                return $row->firstname . ' ' . $row->lastname;
            })
            ->editColumn('birthday', function($row) {
                return $row->birthdate ? Carbon::parse($row->birthdate)->format('F d, Y') : '';
            })
            ->editColumn('documents', function($row) {
                return '';
            })
            ->addColumn('actions', function($row) {
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="' . route('employment-types.edit', $row->id) . '" 
                            class="btn btn-outline-secondary btn ms-1 my-1" 
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button id="btn-delete"
                            class="btn btn-outline-danger btn ms-1 my-1" 
                            data-target="'.route('employment-types.destroy', ['employment_type' => $row->id]).'"
                            title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }


}
