<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class Employee extends Controller
{
    
    public function children(Request $request)
{
    $employee_no = $request->employee_no;
    $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
    $id = $request->id ?? null;

    $query = DB::table('employee_children')
        ->where('employee_no', $employee_no);

    if (!$isDT && $id) {
        $query->where('id', $id);
        $data = $query->first();
        return response()->json($data);
    }

    if ($isDT) {

        $data = $query->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('name', fn($row) => $row->firstname . ' ' . $row->lastname)
            ->editColumn('birthday', fn($row) => $row->birthdate ? Carbon::parse($row->birthdate)->format('F d, Y') : '')
            ->editColumn('documents', function($row) {
                
                if(is_null($row->documents) || empty($row->documents)) {
                    return 'N/A';
                }

                $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

            })
            ->addColumn('actions', function ($row) {

                $deleteRoute = route('hris.employee.children', [
                    'employee_no' => $row->employee_no, 
                    'id' => $row->id
                ]);

                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <button id="btn-edit"
                            class="btn btn-outline-secondary btn ms-1 my-1" 
                            data-id="'.$row->id.'"
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button id="btn-delete"
                            class="btn btn-outline-danger btn ms-1 my-1" 
                            data-target="' . $deleteRoute . '"
                            title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['documents', 'actions'])
            ->make(true);
    }

    return response()->json(['message' => 'Invalid request'], 400);
}


}
