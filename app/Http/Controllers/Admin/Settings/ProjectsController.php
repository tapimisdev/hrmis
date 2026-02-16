<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Services\EmployeeService;

class ProjectsController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);

        $this->middleware('permission:hr.project.view')->only(['index', 'show']);
        $this->middleware('permission:hr.project.assign')->only(['create', 'store']);
        $this->middleware('permission:hr.project.edit')->only('edit', 'update');
        $this->middleware('permission:hr.project.delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {

        if($request->ajax()) {
            $query = DB::table('projects')
                ->get();
                
            return $this->datatable($query);

        }

        return view('admin.pages.settings.projects.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isEdit = false;
        $id = null;
        
        $employees = $this->employeeService->getEmployees(null, null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        return view('admin.pages.settings.projects.form', compact('isEdit', 'id', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'employee_nos' => 'required|array',
            'employee_nos.*' => 'required|exists:employee_information,employee_no',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'field error', 
                'errors'  => $validator->errors()           
            ], 422);
        }

        DB::beginTransaction();

        try {

            $project_id = DB::table('projects')->insertGetId([
                'name' => $payload['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($payload['employee_nos'] as $employee_no) {
                DB::table('employee_projects')->insert([
                    'project_id' => $project_id,
                    'employee_no' => $employee_no,
                    'start_date' => Carbon::parse($payload['start_date'])->format('Y-m-d'),
                    'end_date' => Carbon::parse($payload['end_date'])->format('Y-m-d'),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project ' . strtoupper($payload['name']) . ' Added',
                'redirect' => route('projects.create')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
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

    public function edit(int $id)
    {
        $project = DB::table('projects')
            ->where('id', $id)
            ->first();

        if (!$project) {
            return redirect()->route('projects.index');
        }

        $employee_projects = DB::table('employee_projects')
            ->where('project_id', $id)
            ->get();

        $data = [
            'name' => $project->name,
            'employee_nos' => $employee_projects->pluck('employee_no')->toArray(),
            'start_date' => optional($employee_projects->first())->start_date,
            'end_date' => optional($employee_projects->first())->end_date,
        ];

        $isEdit = true;

        $employees = $this->employeeService->getEmployees(null, null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        return view('admin.pages.settings.projects.form', compact('isEdit', 'id', 'data', 'employees'));
    }


    /**
     * Update the specified resource in storage.
     */
    
    public function update(Request $request, int $id)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'employee_nos' => 'required|array',
            'employee_nos.*' => 'required|exists:employee_information,employee_no',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'field error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
           
            DB::table('projects')
                ->where('id', $id)
                ->update([
                    'name' => $payload['name'],
                    'updated_at' => now(),
                ]);

            DB::table('employee_projects')->where('project_id', $id)->delete();

            $insertData = collect($payload['employee_nos'])->map(function ($employee_no) use ($id, $payload) {
                return [
                    'project_id' => $id,
                    'employee_no' => $employee_no,
                    'start_date' => Carbon::parse($payload['start_date'])->format('Y-m-d'),
                    'end_date' => Carbon::parse($payload['end_date'])->format('Y-m-d'),
                ];
            })->toArray();

            DB::table('employee_projects')->insert($insertData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project ' . strtoupper($payload['name']) . ' updated',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();

        try {

            DB::table('projects')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project has been deleted.',
                'redirect' => ''
            ]);

        } catch(\Exception $e) {
            
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('date_created', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                
                $editRoute = route('projects.edit', [
                    'project' => $row->id
                ]);
                $deleteRoute = route('projects.destroy', [
                    'project' => $row->id
                ]);
                
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="' . $editRoute . '" 
                            class="btn btn-secondary btn ms-1 my-1" 
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button id="btn-delete"
                            class="btn btn-danger btn ms-1 my-1" 
                            data-target="' . $deleteRoute . '"
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
