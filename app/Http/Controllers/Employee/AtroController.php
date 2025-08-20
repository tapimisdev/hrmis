<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreAtroRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AtroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('overtimes')
            ->where('user_id', Auth::user()->id)
            ->get();

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.atro.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employee.pages.atro.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAtroRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {

            $atro = DB::table('overtimes')
                    ->insert([
                        'user_id'       => Auth::user()->id,
                        'date'          => $validatedData['date'],
                        'start_time'    => $validatedData['start_time'],
                        'end_time'      => $validatedData['end_time'],
                        'total_hours'   => $validatedData['total_hours'],
                        'reason'        => $validatedData['reason'],
                        'status'        => 'pending',
                    ]);

            DB::commit();
            return response(['data' => $atro, 'status' => 'store success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage(), 'status' => 'store failed'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $atro = DB::table('overtimes')->where('id', $id)->first();

        if (!$atro) {
            return response()->json(['message' => 'Overtime not found'], 404);
        }

        return response()->json(['atro' => $atro, 'message' => 'success'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $affected = DB::table('overtimes')
                ->where('id', $id)
                ->update(['status' => 'cancelled']);

            DB::commit();
            return response()->json([
                'data' => $affected,
                'message'  => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'destroy failed'
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
           ->addColumn('date', function ($row) {
                return \Carbon\Carbon::parse($row->date)->format('M d, Y');
            })
            ->addColumn('status', function ($row) {
                $status = strtolower($row->status);

                $badgeClass = match ($status) {
                    'pending'   => 'warning',
                    'approved'  => 'success',
                    'rejected'  => 'dark',
                    'cancelled' => 'danger',
                    default     => 'info',
                };

                return '<span class="badge rounded-pill bg-' . $badgeClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $buttons = '
                    <div class="d-flex">
                        <button data-id="' . $row->id . '" 
                            class="btn btn-primary btn-sm ms-1 show-button" 
                            title="Show">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                ';

                // Only show cancel if status is pending or approved
                if (in_array($row->status, ['pending', 'approved'])) {
                    $buttons .= '
                        <button data-id="' . $row->id . '" 
                            class="btn btn-danger btn-sm ms-1 cancel-button" 
                            title="Cancel">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons;
            })
            ->rawColumns(['actions', 'status', 'date'])
            ->make(true);
    }
}
