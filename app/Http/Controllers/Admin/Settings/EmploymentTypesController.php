<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class EmploymentTypesController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if(request()->ajax()) {
            $query = DB::table('employment_types')
                ->orderByDesc('id')
                ->get();
            
            return $this->datatable($query);
        }

        return view('admin.pages.settings.employment-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $isEdit = false;
        $id = null;

        return view('admin.pages.settings.employment-types.form', compact('isEdit', 'id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
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

            DB::table('employment_types')->insert([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'New Employment Type Added',
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $data = DB::table('employment_types')
            ->where('id', $id)
            ->first();

        $isEdit = true;

        return view('admin.pages.settings.employment-types.form', compact('isEdit', 'id', 'data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
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

            DB::table('employment_types')->where('id', $id)
                ->update([
                    'code' => $request->code,
                    'name' => $request->name,
                    'description' => $request->description,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employment Type Updated',
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
    public function destroy(string $id)
    {

        DB::beginTransaction();

        try {

            DB::table('employment_types')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employment Type has been deleted.',
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
               return '
                <div class="d-block d-md-flex gap-2 justify-content-start">
                    <a href="' . route('employment-types.edit', $row->id) . '" 
                        class="btn btn-outline-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                   
                </div>
                ';

                // <button id="btn-delete"
                //     class="btn btn-outline-danger btn ms-1 my-1" 
                //     data-target="'.route('employment-types.destroy', ['employment_type' => $row->id]).'"
                //     title="Delete">
                //         <i class="fa-solid fa-trash-can"></i>
                // </button>
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
