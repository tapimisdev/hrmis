<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreObsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ObsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('obs')
                    ->where('user_id', Auth::user()->id)
                    ->orderBy('created_at', 'desc') // latest first
                    ->get();
        
        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.obs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employee.pages.obs.create');
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreObsRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            // Generate unique obs_no (e.g., OBS-2025-08-0001)
            $year = now()->format('Y');
            $month = now()->format('m');
            $lastObs = DB::table('obs')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->orderByDesc('id')
                ->first();
            $nextNumber = $lastObs ? ((int)substr($lastObs->obs_no, -4)) + 1 : 1;
            $obsNo = 'OBS-' . $year . '-' . $month . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Insert obs record
            $obsId = DB::table('obs')->insertGetId([
                'obs_no'             => $obsNo,
                'user_id'            => Auth::user()->id,
                'date_from'          => $validatedData['date_from'],
                'date_to'            => $validatedData['date_to'],
                'time_out'           => $validatedData['time_out'] ?? null,
                'time_in'            => $validatedData['time_in'] ?? null,
                'destination'        => $validatedData['destination'],
                'purpose'            => $validatedData['purpose'],
                'mode_of_transport'  => $validatedData['mode_of_transport'] ?? null,
                'estimated_expense'  => $validatedData['estimated_expense'] ?? 0,
                'charge_to'          => $validatedData['charge_to'] ?? null,
                'remarks'            => $validatedData['remarks'] ?? null,
                'status'             => 'pending',
                'created_by'         => Auth::user()->id,
                'updated_by'         => Auth::user()->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('obs_attachments', 'public');
                    DB::table('obs_attachments')->insert([
                        'obs_id'     => $obsId,
                        'file_path'  => $path,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response([
                'data' => [
                    'obs_id' => $obsId,
                    'attachments' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
                ],
                'message' => 'OBS application stored successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status'  => 'store failed'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obs = DB::table('obs')
                    ->where('id', $id)
                    ->first();

        $attachments = DB::table('obs_attachments')
                    ->where('obs_id', $obs->id)
                    ->get();
        
        return response(['obs' => $obs, 'attachments' => $attachments, 'status' => 'success'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $affected = DB::table('obs')
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
           ->addColumn('date_range', function ($row) {
                if ($row->date_from == $row->date_to) {
                    // Single day leave
                    return '<span class="badge rounded-pill bg-primary">'
                            . \Carbon\Carbon::parse($row->date_from)->format('M d, Y') .
                        '</span>';
                } else {
                    // Multi-day leave
                    return '<span class="badge rounded-pill bg-primary me-1">'
                            . \Carbon\Carbon::parse($row->date_from)->format('M d, Y') .
                        '</span>' . 'to ' .
                        '<span class="badge rounded-pill bg-success">'
                            . \Carbon\Carbon::parse($row->date_to)->format('M d, Y') .
                        '</span>';
                }
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
            ->rawColumns(['actions', 'status', 'date_range'])
            ->make(true);
    }
}
