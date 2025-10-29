<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreShiftRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('shifts')->where('is_active', true);

        // If it's an AJAX request (usually from DataTables or JS fetch)
        if (request()->ajax()) {
            return $this->datatable($query->get());
        }

        // If it's an API request (e.g., /api/shifts)
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'shifts' => $query->get()
            ]);
        }

        return view('admin.pages.settings.shifts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.shifts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {

        $shift = DB::table('shifts')
            ->insert([
                'name' => $validatedData['name'],
                'earliest_time' => $validatedData['earliest_time'],
                'start_time' => $validatedData['start_time'],
                'break_out_time' => $validatedData['break_out_time'] ?? null,
                'break_in_time' => $validatedData['break_in_time'] ?? null,
                'end_time' => $validatedData['end_time'],
                'minimum_overtime_hours' => $validatedData['minimum_overtime_hours'] ?? 0,
                'is_break_required' => $request->input('is_break_required', 1),
                'is_night_shift' => $request->input('is_night_shift', 0),
                'is_flexible' => $request->input('is_flexible', 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Shift ' . $validatedData['name'] . ' added successfully',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status'   => 'error',
                'message'  => 'Error: ' . $e->getMessage(),
            ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $shift = DB::table('shifts')->where('id', $id)->first();

        if (!$shift) {
            abort(404, 'Shift not found.');
        }

        return response()->json([
            'shift' => $shift
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shift = DB::table('shifts')->where('id', $id)->first();

        if (!$shift) {
            abort(404, 'Shift not found.');
        }

        return view('admin.pages.settings.shifts.edit', compact('shift'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreShiftRequest $request, string $id)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        
        try {

            $shift = DB::table('shifts')->where('id', $id)->first();

            if (!$shift) {
                abort(404, 'Shift not found.');
            }

            DB::table('shifts')->where('id', $id)->update([
                'name' => $validatedData['name'],
                'earliest_time' => $validatedData['earliest_time'] ?? null,
                'start_time' => $validatedData['start_time'] ?? null,
                'break_out_time' => $validatedData['break_out_time'] ?? null,
                'break_in_time' => $validatedData['break_in_time'] ?? null,
                'end_time' => $validatedData['end_time'] ?? null,
                'minimum_overtime_hours' => $validatedData['minimum_overtime_hours'] ?? 0,
                'is_break_required' => $request->input('is_break_required', 1),
                'is_night_shift' => $request->input('is_night_shift', 0),
                'is_flexible' => $request->input('is_flexible', 0),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Shift ' . $validatedData['name'] . ' updated successfully',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status'   => 'error',
                'message'  => 'Error: ' . $e->getMessage(),
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

            $shift = DB::table('shifts')->where('id', $id)->first();

            if (!$shift) {
                abort(404, 'Shift not found.');
            }

            DB::table('shifts')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Shift deleted successfully.'
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
        ->addColumn('earliest_time', function ($row) {
            return $row->earliest_time ? $row->earliest_time : $row->start_time;
        })
        ->addColumn('is_flexible', function ($row) {
            return $row->is_flexible
            ? '<span class="badge bg-success">Yes</span>'
            : '<span class="badge bg-secondary">No</span>';
        })
        ->addColumn('actions', function ($row) {

            return '<div class="d-flex">' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-primary btn ms-1 my-1 show-button" title="View">' .
                    '<i class="fas fa-eye"></i>' .
                '</button>' .
                '<a href="' . route('shift.edit', $row->id) . '" 
                    class="btn btn-outline-secondary btn ms-1 my-1" 
                    title="Edit">
                        <i class="fas fa-edit"></i>
                </a>' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-danger btn ms-1 my-1 delete-button" title="Delete">' .
                    '<i class="fas fa-trash-alt"></i>' .
                '</button>' .
            '</div>';
            
        })
        ->rawColumns(['actions', 'is_flexible'])
        ->make(true);
    }
}
