<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('leaves')->where('is_active', true)->get();
        
        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('admin.pages.settings.leaves.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.leaves.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $leave = DB::table('leaves')->insert([
                'name'              => $validated['name'],
                'is_cumulative'    => $validated['is_cumulative'],
                'no_of_days'        => $validated['no_of_days'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Leave succesfully created.',
                'leave' => $leave
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occured while saving leave.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $leave = DB::table('leaves')->where('id', $id)->where('is_active', true)->first();
        
        if (!$leave) {
            abort(404, 'Leave not found.');
        }

        return response()->json([
            'leave' => $leave
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $leave = DB::table('leaves')->where('id', $id)->first();
        if (!$leave) {
            abort(404, 'Leaves not found.');
        }
        return view('admin.pages.settings.leaves.edit', compact('leave'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreLeaveRequest $request, string $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            $leave = DB::table('leaves')->where('id', $id)->update([
                'name'              => $validated['name'],
                'is_cumulative'     => $validated['is_cumulative'],
                'no_of_days'        => $validated['no_of_days'],
                'updated_at'        => now(),
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Leave updated successfully',
                'leave'   => $leave
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Leave succesfully updated.',
                'error'   => $e->getMessage()
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
            $leave = DB::table('leaves')->where('id', $id)->where('is_active', true);
            
            abort_if(!$leave->exists(), 404, 'Leave not found.');

            $leave->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Leave deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while deleting the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('is_cumulative', function ($row) {
            return $row->is_cumulative
            ? '<span class="badge bg-success">Yes</span>'
            : '<span class="badge bg-danger">No</span>';
        })
        ->addColumn('actions', function ($row) {

            return '<div class="d-flex">' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-primary btn ms-1 my-1 show-button" title="View">' .
                    '<i class="fas fa-eye"></i>' .
                '</button>' .
                '<a href="' . route('settings.leaves.edit', $row->id) . '" 
                    class="btn btn-outline-secondary btn ms-1 my-1" 
                    title="Edit">
                        <i class="fas fa-edit"></i>
                </a>' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-danger btn ms-1 my-1 delete-button" title="Delete">' .
                    '<i class="fas fa-trash-alt"></i>' .
                '</button>' .
            '</div>';
            
        })
        ->rawColumns(['actions', 'is_cumulative'])
        ->make(true);
    }
}
