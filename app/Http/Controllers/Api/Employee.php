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
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function education(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_education')
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
                ->editColumn('level', fn($row) => $row->level)
                ->editColumn('school_name', fn($row) => $row->school_name)
                ->editColumn('course', fn($row) => $row->course)
                ->editColumn('year_graduated', fn($row) => Carbon::parse($row->year_graduated)->format('M d, Y'))
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.education', [
                        'employee_no' => $row->employee_no, 
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function civil_service(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_civil_service')
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
                ->editColumn('certification', fn($row) => $row->certification)
                ->editColumn('rating', fn($row) => $row->rating)
                ->editColumn('license_no', fn($row) => $row->license_no)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.civil-service', [
                        'employee_no' => $row->employee_no, 
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function work_experience(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_work_experience')
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
                ->editColumn('position', fn($row) => $row->position)
                ->editColumn('department', fn($row) => $row->department)
                ->editColumn('employment_status', fn($row) => $row->employment_status)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.work-experience', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function voluntary_works(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_voluntary_works')
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
                ->editColumn('organization', fn($row) => $row->organization)
                ->editColumn('consumed_hours', fn($row) => $row->consumed_hours)
                ->editColumn('position', fn($row) => $row->position)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.voluntary-works', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function trainings(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_trainings')
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
                ->editColumn('name', fn($row) => $row->name)
                ->editColumn('consumed_hours', fn($row) => $row->consumed_hours)
                ->editColumn('sponsored_by', fn($row) => $row->sponsored_by)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.trainings', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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

    public function skills(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_skills_hobbies')
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
                ->editColumn('name', fn($row) => $row->name)
                ->editColumn('recognition', fn($row) => $row->recognition)
                ->editColumn('organization', fn($row) => $row->organization)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/uploads/employees/' . $row->employee_no . '/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.skills', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
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
