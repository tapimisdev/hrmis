<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreHolidayRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class HolidayController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('holidays')->where('is_active', true)->get();
        if (request()->ajax()) {
            return $this->datatable($query);
        }
        return view('admin.pages.settings.holiday.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.holiday.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHolidayRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $holiday = DB::table('holidays')->insert([
                'name' => $validated['name'],
                'date' => $validated['date'],
                'type' => $validated['type'],
                'is_repeating' => $request->input('is_repeating', 0),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Holiday saved successfully.',
                'holiday' => $holiday
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while saving the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $holiday = DB::table('holidays')->where('id', $id)->first();
        if (!$holiday) {
            abort(404, 'Holiday not found.');
        }
        return response()->json([
            'holiday' => $holiday
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $holiday = DB::table('holidays')->where('id', $id)->first();
        if (!$holiday) {
            abort(404, 'Holiday not found.');
        }
        return view('admin.pages.settings.holiday.edit', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreHolidayRequest $request, $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $holiday = DB::table('holidays')->where('id', $id)->first();
            if (!$holiday) {
                abort(404, 'Holiday not found.');
            }
            DB::table('holidays')->where('id', $id)->update([
                'name' => $validated['name'],
                'date' => $validated['date'],
                'type' => $validated['type'],
                'is_repeating' => $request->input('is_repeating', 0),
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Holiday updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while updating the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $holiday = DB::table('holidays')->where('id', $id)->first();
            if (!$holiday) {
                abort(404, 'Holiday not found.');
            }
            DB::table('holidays')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Holiday deleted successfully.'
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
        ->addColumn('date', function ($row) {

            if(!$row->is_repeating) {
                return \Carbon\Carbon::parse($row->date)->format('M d, Y');
            }

            return \Carbon\Carbon::parse($row->date)->format('M d');
        })
        ->addColumn('is_repeating', function ($row) {
            return $row->is_repeating
            ? '<span class="badge bg-success">Yes</span>'
            : '<span class="badge bg-secondary">No</span>';
        })
        ->addColumn('actions', function ($row) {

            return '<div class="d-flex">' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-primary btn ms-1 my-1 show-button" title="View">' .
                    '<i class="fas fa-eye"></i>' .
                '</button>' .
                '<a href="' . route('holiday.edit', $row->id) . '" 
                    class="btn btn-outline-secondary btn ms-1 my-1" 
                    title="Edit">
                        <i class="fas fa-edit"></i>
                </a>' .
                '<button data-id="' . $row->id . '" class="btn btn-outline-danger btn ms-1 my-1 delete-button" title="Delete">' .
                    '<i class="fas fa-trash-alt"></i>' .
                '</button>' .
            '</div>';
            
        })
        ->rawColumns(['actions', 'date', 'is_repeating'])
        ->make(true);
    }
}
