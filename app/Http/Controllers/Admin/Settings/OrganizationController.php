<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if(request()->ajax()) {
            $type = request()->type;

            if ($type === 'division') {
                $query = DB::table('divisions')
                    ->orderByDesc('id')
                    ->get();
            } elseif ($type === 'unit') {
                 $query = DB::table('units')
                        ->select('units.*', 'divisions.name as division_name', 'divisions.code as division_code')
                        ->leftJoin('divisions', 'units.division_id', '=', 'divisions.id')
                        ->orderByDesc('units.id');

                $division_id = request()->division_id;
                if ($division_id) {
                    $query->where('division_id', $division_id);
                }

                $query->get();
                
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid Type']);
            }

            return $this->datatable($query, $type);
        }

        $agency = DB::table('agency')->first();
        $divisions = DB::table('divisions')->get();

        return view('admin.pages.settings.organization.index', compact('agency', 'divisions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $type = $request->type;

        if(is_null($type) || !in_array($type, ['division', 'unit'])) return redirect()->route('organization.index');
        
        $isEdit = false;

        if($type === 'division') {
            return view('admin.pages.settings.organization.division-form', compact('isEdit', 'type'));
        } elseif($type === 'unit') {
            $divisions = DB::table('divisions')->get();
            return view('admin.pages.settings.organization.unit-form', compact('isEdit', 'type', 'divisions'));
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $type = $request->type;
        
        if(!in_array($type, ['agency', 'division', 'unit'])) {
             return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: Invalid Type'
            ]);
        }

        # UPDATE AGENCY
        if($type == 'agency') {

            $request->validate([
                'agency_name' => 'required|string|max:255',
                'agency_code' => 'required|string|max:255',
                'agency_description' => 'nullable|string',
            ]);

            DB::beginTransaction();
            
            try {
                DB::table('agency')
                    ->where('id', 1)
                    ->update([
                        'code' => $request->agency_code,
                        'name' => $request->agency_name,
                        'description' => $request->agency_description,
                        'updated_at' => now(),
                    ]);
                
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Agency Information Updated',
                    'redirect' => ''
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error Occured: ' . $e->getMessage()
                ]);
            }

        }

        # CREATE DIVISION
        if($type == 'division') {

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            DB::beginTransaction();
            
            try {
                DB::table('divisions')->insert([
                    'name' => $request->name,
                    'code' => $request->code,
                    'description' => $request->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'New Division Created',
                    'redirect' => '_self'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error Occured: ' . $e->getMessage()
                ]);
            }

        }

        # CREATE UNIT
        if($type == 'unit') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'description' => 'nullable|string',
                'division_id' => 'required|exists:divisions,id',
            ]); 

            DB::beginTransaction();

            try {
                DB::table('units')->insert([
                    'name' => $request->name,
                    'code' => $request->code,
                    'description' => $request->description,
                    'division_id' => $request->division_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'New Unit Created',
                    'redirect' => '_self'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Error Occured: ' . $e->getMessage()
                ]);
            }

        }

    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        
        $type = $request->type;

        if(is_null($type) || !in_array($type, ['division', 'unit'])) return redirect()->route('organization.index');
        
        $isEdit = true;

        if($type === 'division') {
            $data = DB::table('divisions')
                ->where('id', $id)
                ->first() ?? [];
            return view('admin.pages.settings.organization.division-form', compact('isEdit', 'type', 'id', 'data'));
        } elseif($type === 'unit') {
            $divisions = DB::table('divisions')->get();
            $data = DB::table('units')
                ->where('id', $id)
                ->first() ?? [];
            return view('admin.pages.settings.organization.unit-form', compact('isEdit', 'type', 'id', 'data', 'divisions'));
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $type = $request->type;

        if (!in_array($type, ['division', 'unit'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: Invalid Type'
            ]);
        }

        // UPDATE DIVISION
        if ($type == 'division') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            DB::beginTransaction();

            try {
                DB::table('divisions')
                    ->where('id', $id)
                    ->update([
                        'name' => $request->name,
                        'code' => $request->code,
                        'description' => $request->description,
                        'updated_at' => now(),
                    ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Division Updated',
                    'redirect' => ''
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error Occured: ' . $e->getMessage()
                ]);
            }
        }

        // UPDATE UNIT
        if ($type == 'unit') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'description' => 'nullable|string',
                'division_id' => 'required|exists:divisions,id',
            ]);

            DB::beginTransaction();

            try {
                DB::table('units')
                    ->where('id', $id)
                    ->update([
                        'name' => $request->name,
                        'code' => $request->code,
                        'description' => $request->description,
                        'division_id' => $request->division_id,
                        'updated_at' => now(),
                    ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Unit Updated',
                    'redirect' => ''
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error Occured: ' . $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {

        if(!in_array($request->type, ['division', 'unit'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: Invalid Type'
            ]);
        }
        
        DB::beginTransaction();

        try {

            DB::table($request->type . 's')
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

    public function datatable($query, $type)
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
            ->editColumn('division', function ($row) use ($type) {
                if ($type === 'unit') {
                    return $row->division_name ?? '';
                }
            })
            ->addColumn('actions', function ($row) use ($type) {
                $editRoute = $type === 'unit'
                    ? route('organization.edit', ['organization' => $row->id, 'type' => 'unit'])
                    : route('organization.edit', ['organization' => $row->id, 'type' => 'division']);
                $deleteRoute = $type === 'unit'
                    ? route('organization.destroy', ['organization' => $row->id, 'type' => 'unit'])
                    : route('organization.destroy', ['organization' => $row->id, 'type' => 'division']);

                return '
                    <a href="' . $editRoute . '" 
                        class="btn btn-secondary btn ms-1" 
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <button id="btn-delete"
                        class="btn btn-danger btn ms-1" 
                        data-target="' . $deleteRoute . '"
                        title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
        }
}
