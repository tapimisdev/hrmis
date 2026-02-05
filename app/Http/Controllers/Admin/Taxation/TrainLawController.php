<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class TrainLawController extends Controller
{
    public function index()
    {
        if (request()->wantsJson()) {
            $trainLaws = DB::table('train_law')
                ->where('is_active', true)
                ->get();

            return DataTables::of($trainLaws)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {

                    if ($row->is_active) {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('actions', function ($row) {
                    return '<div class="d-flex">' .
                        '<button data-id="' . $row->id . '" 
                                class="btn btn-warning ms-1 my-1 edit-button" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>' .
                        '<button data-id="' . $row->id . '" 
                                class="btn btn-danger ms-1 my-1 inactive-button" 
                                title="Set Inactive">
                            <i class="fas fa-toggle-off"></i>
                        </button>' .
                    '</div>';
                })
                ->rawColumns(['actions', 'status'])
                ->make(true);
        }

        return view('admin.pages.taxation.train-law.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'string', 'max:10', 'unique:train_law,year'],
        ]);

        DB::table('train_law')->insert([
            'year' => $validated['year'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Train Law created successfully.',
        ]);
    }

    public function edit(string $id)
    {
        $row = DB::table('train_law')
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Train Law not found.'], 404);
        }

        return response()->json($row);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'year' => ['required', 'string', 'max:10', 'unique:train_law,year,' . $id],
        ]);

        $updated = DB::table('train_law')
            ->where('id', $id)
            ->where('is_active', true)
            ->update([
                'year' => $validated['year'],
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Train Law not found or inactive.'], 404);
        }

        return response()->json([
            'message' => 'Train Law updated successfully.',
        ]);
    }

    public function setInactive(string $id)
    {
        $updated = DB::table('train_law')
            ->where('id', $id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Train Law not found.'], 404);
        }

        return response()->json([
            'message' => 'Train Law set to inactive.',
        ]);
    }
}
