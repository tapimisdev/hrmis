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

    public function getRawData(?int $id = null)
    {
        $user_id = Auth::id();

        // Fetch main OBS applications
        $applications = DB::table('obs as ob')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', DB::raw("(SELECT employee_no FROM employee_information WHERE user_id = ob.user_id LIMIT 1)"))
            ->select(
                'ob.*',
                DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as employee_name")
            )
            ->where('ob.user_id', $user_id)
            ->when($id, function ($query, $id) {
                return $query->where('ob.id', $id);
            })
            ->orderBy('ob.created_at', 'desc')
            ->get();

        $applicationIds = $applications->pluck('id');

        // Fetch attachments
        $attachments = DB::table('obs_attachments')
            ->select('obs_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('obs_id', $applicationIds)
            ->get()
            ->groupBy('obs_id');

        // Fetch approvals and group by level, deduplicated by user
        $approvalsRaw = DB::table('obs_approvals')
            ->join('employee_information', 'obs_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select([
                'obs_approvals.obs_id',
                'obs_approvals.user_id',
                'obs_approvals.level',
                'obs_approvals.status',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.lastname',
            ])
            ->whereIn('obs_approvals.obs_id', $applicationIds)
            ->get();

        // Group approvals by application id for further use
        $approvalsByApplication = $approvalsRaw->groupBy('obs_id');

        // Prepare level_approvals (status per level per application)
        $levelApprovals = DB::table('obs_approvals')
            ->select('obs_id', 'level', 'status')
            ->whereIn('obs_id', $applicationIds)
            ->orderBy('level')
            ->get()
            ->groupBy('obs_id')
            ->map(function ($group) {
                return $group->groupBy('level')->map(function ($levelGroup) {
                    // Prioritize approved or rejected, fallback to first status
                    foreach ($levelGroup as $row) {
                        if (in_array($row->status, ['approved', 'rejected'])) {
                            return $row->status;
                        }
                    }
                    return $levelGroup->first()->status;
                });
            });

        // Group approvals by level across all applications (for display)
        $groupedArray = $approvalsRaw
            ->groupBy('level')
            ->map(function ($items) {
                return $items->unique('user_id')->values();
            })
            ->sortKeys()
            ->toArray();

        // Merge all data into final results
        $results = $applications->map(function ($item) use ($attachments, $groupedArray, $levelApprovals) {
            $item->attachments = $attachments->get($item->id)?->values() ?? [];
            $item->approvals = $groupedArray;
            $item->level_approvals = $levelApprovals->get($item->id)?->toArray() ?? [];
            return $item;
        });

        return $results;
    }

     public function getData()
    {
        // Get active leave types
        $leaves = DB::table('leaves')
            ->where('is_active', true)
            ->get();

        // Get current user and employee_no
        $user = Auth::user()->load('employeeInformation');
        $employee_no = $user->employeeInformation->employee_no ?? null;

        // Get user's organization
        $organization = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->latest()
            ->first();

        // Get leave approvers for the user's division and unit
        $approvers = DB::table('application_approver')
            ->leftJoin('application_approver_users', 'application_approver.id', '=', 'application_approver_users.application_approver_id')
            ->leftJoin('users', 'application_approver_users.user_id', '=', 'users.id')
            ->where('application_approver.type', 'pass_slip')
            ->where('application_approver.division_id', $organization->division_id)
            ->where('application_approver.unit_id', $organization->unit_id)
            ->select(
                'application_approver_users.level',
                'users.id as user_id',
                'users.name as user_name',
            )
            ->get();

        // Group approvers by level
        $approvers = $approvers
            ->groupBy('level')
            ->mapWithKeys(function ($items, $level) {
                return [
                    $level => $items->map(function ($item) {
                        return [
                            'id'   => $item->user_id,
                            'name' => $item->user_name,
                        ];
                    })->unique('id')->values()
                ];
            })
            ->sortKeys();

        // 1. Get leave application dates (per row)
        $applications = DB::table('leave_applications as la')
            ->join('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
            ->where('la.user_id', $user->id) // optional: filter by current user
            ->select(
                DB::raw("'leave' as title"),
                'la.status',
                'ld.date'
            );

        // 2. Get holidays (with fixed 'holiday' name and empty status)
        $holidays = DB::table('holidays')
            ->select(
                'name as title',
                DB::raw("'holiday' as status"),
                'date'
            );

        // 3. Get suspensions (from suspension_dates)
        $suspensions = DB::table('suspension as s')
            ->join('suspension_dates as sd', 's.id', '=', 'sd.suspension_id')
            ->where('s.isActive', true)
            ->select(
                'name as title',
                DB::raw("'suspension' as status"),
                'sd.date'
            );

        // Combine all into one collection
        $allApplications = $applications
            ->unionAll($holidays)
            ->unionAll($suspensions)
            ->orderBy('date')
            ->get();

        return [
            'leaves' => $leaves,
            'approvers' => $approvers,
            'applications' => $allApplications
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {

            $data = $this->getRawData();

            return $this->datatable($data);
        }

        return view('employee.pages.obs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = $this->getData();
        $leaves = $data['leaves'];
        $approvers = $data['approvers'];
        $applications = $data['applications'];

        return view('employee.pages.obs.create', compact('leaves', 'approvers', 'applications'));
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreObsRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {

            if(empty($validatedData['approvers'])) {
                return response([
                    'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
                    'status'  => 'error'
                ], 500); 
            }

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
            $approvers = $validatedData['approvers'];

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
                'level'              => 1,
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

            foreach ($approvers as $level => $approverList) {
                foreach ($approverList as $userId) {
                    DB::table('obs_approvals')->insertGetId([
                        'obs_id' => $obsId,
                        'user_id'              => $userId,
                        'level'                => $level,
                        'status'               => 'pending',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been submitted',
                'redirect' => route('obs.create')
            ]);

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
    public function show(int $id)
    {
        
        $data = $this->getRawData($id)[0] ?? [];

        if(!$data) {
            return redirect()->route('leave.index');
        }
         
        return response(['data' => $data, 'status' => 'success'], 200);
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
