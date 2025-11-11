<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $employment_types;

    public function __construct() {
        $this->employment_types = DB::table('employment_types')->get();
        
        $this->middleware('permission:hr.position.view')->only(['index', 'show']);
        $this->middleware('permission:hr.position.create')->only(['create', 'store']);
        $this->middleware('permission:hr.position.edit')->only('edit', 'update');
        $this->middleware('permission:hr.position.delete')->only('destroy');
    }

    public function index(Request $request)
    {

        $employment_type_id = $request->employment_type;

        if ($employment_type_id) {
            $employment_type = DB::table('employment_types')
                ->where('id', $employment_type_id)
                ->first();

            if (!$employment_type) {
                return redirect()->back()->with('error', 'Invalid employment type.');
            }

            if($request->ajax()) {

                $query = DB::table('positions')
                    ->where('employment_type_id', $employment_type_id)
                    ->get();
                    
                return $this->datatable($query);

            }

            $employment_types = $this->employment_types;

            return view('admin.pages.settings.positions.index', [
                'employment_type' => $employment_type
            ], compact('employment_type', 'employment_types'));
        }

        
        $employment_type = DB::table('employment_types')->first();
        
        if (!$employment_type) {
            return redirect()->back()->with('error', 'No employment types found.');
        }

        return redirect()->route('positions.index', [
            'employment_type' => $employment_type->id
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(int $employment_type_id)
    {

        $isEdit = false;
        $id = null;

        $employment_type = DB::table('employment_types')
            ->where('id', $employment_type_id)
            ->first();
    
        return view('admin.pages.settings.positions.form', compact('isEdit', 'id', 'employment_type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(int $employment_type_id, Request $request)
    {

        $payload = array_merge($request->all(), [
            'employment_type_id' => $employment_type_id
        ]);

        $validator = Validator::make($payload, [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'employment_type_id' => 'required|exists:employment_types,id',
            'description' => 'nullable|string',
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

            DB::table('positions')->insert([
                'code' => $request->code,
                'name' => $request->name,
                'employment_type_id' => $request->employment_type_id,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Position ' . strtoupper($request->name) . ' Added',
                'redirect' => '_self'
            ]);

        } catch(\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
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
    public function edit(int $employment_type_id, string $id)
    {
        $data = DB::table('positions')
            ->where('id', $id)
            ->first();

        $employment_type = DB::table('employment_types')
            ->where('id', $employment_type_id)
            ->first();

        $isEdit = true;

        return view('admin.pages.settings.positions.form', compact('isEdit', 'id', 'employment_type', 'data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $employment_type_id, Request $request, string $id)
    {

        $payload = array_merge($request->all(), [
            'employment_type_id' => $employment_type_id
        ]);

        $validator = Validator::make($payload, [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'employment_type_id' => 'required|exists:employment_types,id',
            'description' => 'nullable|string',
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

            DB::table('positions')->where('id', $id)
                ->update([
                    'code' => $request->code,
                    'name' => $request->name,
                    'employment_type_id' => $request->employment_type_id,
                    'description' => $request->description,
                    'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Position ' . strtoupper($request->name) . ' Updated',
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

    /**
     * Remove the specified resource from storage.
     */
    
    public function destroy(int $employment_type_id, string $id)
    {
        DB::beginTransaction();

        try {
            DB::table('positions')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Position has been deleted.',
                'redirect' => ''
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($e->getCode() === '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to delete position because it is assigned to one or more users.'
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('code', function ($row) {
                return $row->code;
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('date_created', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                
                $editRoute = route('positions.edit', [
                    'employment_type_id' => $row->employment_type_id,
                    'id' => $row->id
                ]);

                $deleteRoute = route('positions.destroy', [
                    'employment_type_id' => $row->employment_type_id, 
                    'id' => $row->id
                ]);
                
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="" 
                            class="btn btn-primary btn ms-1 my-1" 
                            title="Show">
                                <i class="fa-solid fa-eye"></i>
                        </a>
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
