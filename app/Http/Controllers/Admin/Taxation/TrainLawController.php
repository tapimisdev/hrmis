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

        if (!request()->wantsJson()) {
            return view('admin.pages.taxation.train-law.index');
        }

        $trainLaws = DB::table('train_law')
            ->get();

        return DataTables::of($trainLaws)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                if ($row->is_active) {
                    return '
                            <span class="badge rounded-pill bg-success px-3 py-2">
                                <i class="bi bi-check-circle "></i> Active
                            </span>
                        ';
                }

                return '
                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                            <i class="bi bi-x-circle"></i> Inactive
                        </span>
                    ';
            })
            ->addColumn('timestamp', function ($row) {
                $created = $row->created_at ? date('M d, Y • h:i A', strtotime($row->created_at)) : '-';
                $updated = $row->updated_at ? date('M d, Y • h:i A', strtotime($row->updated_at)) : '-';

                return '
                        <div class="small">
                            <div class="text-muted">
                                <i class="bi bi-calendar-plus me-1"></i>
                                <span class="fw-semibold">Created:</span> ' . $created . '
                            </div>
                            <div class="text-muted">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <span class="fw-semibold">Updated:</span> ' . $updated . '
                            </div>
                        </div>
                    ';
            })
            ->addColumn('actions', function ($row) {

                $manageUrl = route('taxation.train-law-items.index', ['trainLawId' => $row->id]);

                if (!$row->is_active) {
                    return
                        '<div class="d-flex">' .
                            '<button
                                data-id="' . $row->id . '"
                                data-status="' . $row->is_active . '"
                                class="btn btn-success ms-1 my-1 toggle-button text-light"
                                title="Activate">
                                <i class="fas fa-toggle-on"></i>
                            </button>' .

                        '</div>';
                }

                return
                    '<div class="d-flex">' .

                        '<a href="' . $manageUrl . '"
                            class="btn btn-primary ms-1 my-1 text-light"
                            title="Manage Items">
                            <i class="fas fa-list-check"></i>
                        </a>' .

                        '<button
                            data-id="' . $row->id . '"
                            class="btn btn-warning ms-1 my-1 edit-button"
                            title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>' .

                        '<button
                            data-id="' . $row->id . '"
                            data-status="' . $row->is_active . '"
                            class="btn btn-danger ms-1 my-1 toggle-button"
                            title="Deactivate">
                            <i class="fas fa-toggle-off"></i>
                        </button>' .

                    '</div>';
            })
            ->rawColumns(['actions', 'status', 'timestamp'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'numeric', 'max:9999', 'unique:train_law,year'],
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
                'is_active' => DB::raw('NOT is_active'),
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
