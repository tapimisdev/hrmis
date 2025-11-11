<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WeeklyScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.weekly_schedule.view')->only(['index', 'show']);
        $this->middleware('permission:hr.weekly_schedule.create')->only(['create', 'store']);
        $this->middleware('permission:hr.weekly_schedule.edit')->only('edit', 'update', 'updateRole', 'editRole');
        $this->middleware('permission:hr.weekly_schedule.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('work_schedule')->where('is_active', true);

        // Handle AJAX requests (e.g., DataTables)
        if (request()->ajax()) {
            return $this->datatable($query->get());
        }

        // Handle API requests
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'weekly_schedules' => $query->get()
            ]);
        }

        // Default: return Blade view
        return view('admin.pages.settings.weekly-schedules.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.weekly-schedules.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\Admin\Settings\StoreWeeklyScheduleRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            $schedule = DB::table('work_schedule')->insert([
                'name' => $validatedData['name'],
                'is_monday' => $request->input('is_monday', 1),
                'is_tuesday' => $request->input('is_tuesday', 1),
                'is_wednesday' => $request->input('is_wednesday', 1),
                'is_thursday' => $request->input('is_thursday', 1),
                'is_friday' => $request->input('is_friday', 1),
                'is_saturday' => $request->input('is_saturday', 0),
                'is_sunday' => $request->input('is_sunday', 0),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Weekly Schedule saved successfully.',
                'schedule' => $schedule
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while saving the weekly schedule.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $schedule = DB::table('work_schedule')->where('id', $id)->first();
        if (!$schedule) {
            return response()->json([
                'message' => 'Weekly Schedule not found.'
            ], 404);
        }
        return response()->json([
            'schedule' => $schedule
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $schedule = DB::table('work_schedule')->where('id', $id)->first();
        if (!$schedule) {
            return response()->json([
                'message' => 'Weekly Schedule not found.'
            ], 404);
        }
        return view('admin.pages.settings.weekly-schedules.edit', compact('schedule'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\Admin\Settings\StoreWeeklyScheduleRequest $request, string $id)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try {
            $schedule = DB::table('work_schedule')->where('id', $id)->first();
            if (!$schedule) {
                return response()->json([
                    'message' => 'Weekly Schedule not found.'
                ], 404);
            }
            DB::table('work_schedule')->where('id', $id)->update([
                'name' => $validatedData['name'],
                'is_monday' => $request->input('is_monday', 1),
                'is_tuesday' => $request->input('is_tuesday', 1),
                'is_wednesday' => $request->input('is_wednesday', 1),
                'is_thursday' => $request->input('is_thursday', 1),
                'is_friday' => $request->input('is_friday', 1),
                'is_saturday' => $request->input('is_saturday', 0),
                'is_sunday' => $request->input('is_sunday', 0),
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Weekly Schedule updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while updating the weekly schedule.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {

            $works_schedule = DB::table('work_schedule')->where('id', $id)->first();

            if (!$works_schedule) {
                return response()->json([
                    'message' => 'Weekly Schedule not found.'
                ], 404);
            }

            DB::table('work_schedule')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Weekly Schedule deleted successfully.'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while deleting the shift.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('actions', function ($row) {

            return '<div class="d-flex">' .
                '<button data-id="' . $row->id . '" class="btn btn-primary btn ms-1 my-1 show-button" title="View">' .
                    '<i class="fas fa-eye"></i>' .
                '</button>' .
                '<a href="' . route('weekly-schedules.edit', $row->id) . '" 
                    class="btn btn-secondary btn ms-1 my-1" 
                    title="Edit">
                        <i class="fas fa-edit"></i>
                </a>' .
                '<button data-id="' . $row->id . '" class="btn btn-danger btn ms-1 my-1 delete-button" title="Delete">' .
                    '<i class="fas fa-trash-alt"></i>' .
                '</button>' .
            '</div>';
            
        })
        ->rawColumns(['actions', 'is_flexible'])
        ->make(true);
    }
}
