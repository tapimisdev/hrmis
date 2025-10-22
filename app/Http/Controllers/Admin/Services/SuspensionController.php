<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SuspensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        if($request->ajax()) {

            $query = DB::table('suspension')
                ->get();
            
            return $this->datatable($query); 
        }

        return view('admin.pages.services.suspension.index');   
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $isEdit = false;
        $id = null;
    
        return view('admin.pages.services.suspension.form', compact('isEdit', 'id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $payload = $request->all();

        // dd($payload);   

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'description' => 'nullable',
            'suspensions'                 => 'required|array|min:1',
            'suspensions.*.date'          => 'required|date',
            'suspensions.*.type'          => 'required|in:whole_day,half_day',
            'suspensions.*.shift'     => 'nullable|required_if:suspensions.*.type,half_day|in:morning,afternoon',
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

            $suspensionId = DB::table('suspension')->insertGetId([
                'events_announcements_id' => null,
                'name'                    => $request->name,
                'description'             => $request->description,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            foreach ($request->suspensions as $suspensionDate) {
                DB::table('suspension_dates')->insert([
                    'suspension_id' => $suspensionId,
                    'date'          => Carbon::parse($suspensionDate['date'])->format('Y-m-d'),
                    'type'          => $suspensionDate['type'],
                    'shift'         => $suspensionDate['shift']
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Suspension ' . strtoupper($request->name) . ' Added',
                'redirect' => route('services.suspensions.index')
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
    public function show(int $id)
    {
        $suspension = DB::table('suspension_dates')
                    ->leftJoin('suspension', 'suspension_dates.suspension_id', '=', 'suspension.id')
                    ->where('suspension_dates.id', $id)
                    ->where('suspension.is_active', true)
                    ->first();
                    
        return response()->json($suspension);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {

        $suspension = DB::table('suspension')
            ->where('id', $id)
            ->first();
        if(!$suspension) {
            return redirect()->route('services.suspensions.index');
        }
        
        if ($suspension) {
            $suspensionDates = json_decode(json_encode(
                DB::table('suspension_dates')
                    ->where('suspension_id', $suspension->id)
                    ->select('id', 'date', 'type', 'shift')
                    ->get()
            ), true);
        } else {
            $suspensionDates = [];
        }

        $data = [
            'id'       => $suspension->id ?? null,
            'name'     => $suspension->name ?? null,
            'description' => $suspension->description ?? null,
            'suspensions'         => $suspensionDates,
        ];

        $id = $data['id'];
        $isEdit = true;

        return view('admin.pages.services.suspension.form', compact('isEdit', 'id', 'data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name'                      => 'required|string|max:255',
            'description'               => 'nullable|string',
            'suspensions'               => 'required|array|min:1',
            'suspensions.*.date'        => 'required|date',
            'suspensions.*.type'        => 'required|in:whole_day,half_day',
            'suspensions.*.shift'   => 'nullable|required_if:suspensions.*.type,half_day|in:morning,afternoon',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            DB::table('suspension')->where('id', $id)->update([
                'name'        => $request->name,
                'description' => $request->description,
                'updated_at'  => now(),
            ]);

            DB::table('suspension_dates')->where('suspension_id', $id)->delete();

            foreach ($request->suspensions as $suspensionDate) {
                DB::table('suspension_dates')->insert([
                    'suspension_id' => $id,
                    'date'          => Carbon::parse($suspensionDate['date'])->format('Y-m-d'),
                    'type'          => $suspensionDate['type'],
                    'shift'     => $suspensionDate['type'] === 'half_day' ? $suspensionDate['shift'] : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Suspension ' . strtoupper($request->name) . ' Updated',
                'redirect' => ''
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();

        try {

            DB::table('suspension')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Suspension has been deleted.',
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

    public function deleteOnlyDate($id)
    {
        DB::beginTransaction();

        try {
            $suspensionDate = DB::table('suspension_dates')->where('id', $id)->first();

            if (!$suspensionDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Suspension date not found.',
                ], 404);
            }

            DB::table('suspension_dates')
                ->where('id', $id)
                ->update([
                    'isActive' => false,
                ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Suspension date has been deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('date_added', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                
                $editRoute = route('services.suspensions.edit', [
                    'suspension' => $row->id
                ]);
                $deleteRoute = route('services.suspensions.destroy', [
                    'suspension' => $row->id
                ]);
                
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="javascript:void(0)" id="btn-show"
                            class="btn btn-outline-primary btn ms-1 my-1 " 
                            title="Show">
                                <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="' . $editRoute . '" 
                            class="btn btn-outline-secondary btn ms-1 my-1" 
                            title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button id="btn-delete"
                            class="btn btn-outline-danger btn ms-1 my-1" 
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
