<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveApplication;
use Illuminate\Http\Request;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;

use Carbon\Carbon;

class LeaveApplicationController extends Controller
{
    protected $employee_service;

    public function __construct(EmployeeService $employee_service)
    {
        $this->employee_service = $employee_service;
    }

    public function getRawData(?int $id = null)
    {
        $user_id = Auth::id();

        // Fetch main leave applications
        $applications = DB::table('leave_applications as la')
            ->leftJoin('leaves as l', 'la.leave_id', '=', 'l.id')
            ->leftJoin('leave_dates as ld', 'ld.leave_application_id', '=', 'la.id')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'la.employee_no')
            ->select(
                'la.*',
                'l.name as leave_name',
                DB::raw('GROUP_CONCAT(DISTINCT ld.date) as dates'),
                DB::raw('MAX(ep.firstname) as firstname'),
                DB::raw('MAX(ep.lastname) as lastname')
            )
            ->where('la.user_id', $user_id)
            ->when($id, function ($query, $id) {
                return $query->where('la.id', $id);
            })
            ->groupBy(
                'la.id',
                'l.name',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.leave_id',
                'la.days',
                'la.reason',
                'la.status',
                'la.created_at',
                'la.updated_at'
            )
            ->orderBy('la.created_at', 'desc')
            ->get();


        $applicationIds = $applications->pluck('id');

        // Fetch attachments
        $attachments = DB::table('leave_attachments')
            ->select('leave_application_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('leave_application_id', $applicationIds)
            ->get()
            ->groupBy('leave_application_id');

        // Fetch approvals and group by level, deduplicated by user
        $approvalsRaw = DB::table('leave_approvals')
            ->join('employee_information', 'leave_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select([
                'leave_approvals.leave_application_id',
                'leave_approvals.user_id',
                'leave_approvals.level',
                'leave_approvals.status',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.lastname',
            ])
            ->whereIn('leave_approvals.leave_application_id', $applicationIds)
            ->get();

        // Group approvals by application id for further use
        $approvalsByApplication = $approvalsRaw->groupBy('leave_application_id');

        // Prepare level_approvals (status per level per application)
        $levelApprovals = DB::table('leave_approvals')
            ->select('leave_application_id', 'level', 'status')
            ->whereIn('leave_application_id', $applicationIds)
            ->orderBy('level')
            ->get()
            ->groupBy('leave_application_id')
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

        // Group for approvals by level + unique users (as before)
        $groupedArray = $approvalsRaw
            ->groupBy('level')
            ->map(function ($items) {
                return $items->unique('user_id')->values();
            })
            ->sortKeys()
            ->toArray();

        // Merge all data into final results
        $results = $applications->map(function ($item) use ($attachments, $groupedArray, $levelApprovals) {
            $item->dates = $item->dates ? explode(',', $item->dates) : [];
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
            ->where('application_approver.type', 'leave')
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

        return view('employee.pages.leave.index');
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

        return view('employee.pages.leave.create', compact('leaves', 'approvers', 'applications'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveApplication $request) 
    {
        $validatedData = $request->validated();

        if(!empty($validatedData['user_id'])) {
            $user = User::with('employeeInformation')->findOrFail($validatedData['user_id']);
            $employee_no = $user->employeeInformation->employee_no;
            $user_id = $user->id;
        } else { 
            $user = Auth::user()->load('employeeInformation');
            $employee_no = $user->toArray()['employee_information']['employee_no'];
            $user_id = Auth::user()->id;
        }

        $organization = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->first();

        DB::beginTransaction();

        try {

            if(empty($validatedData['approvers'])) {
                return response([
                    'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
                    'status'  => 'error'
                ], 500); 
            }

            $dates = json_decode($validatedData['selectedDates'], true);
            $approvers = $validatedData['approvers'];
            $days = count($approvers);

            $leaveName = DB::table('leaves')
                ->where('id', $validatedData['leave_id'])
                ->pluck('name')
                ->first();

            $application_no = generateApplicationNo('leave_applications', 'LV');

            $applicationID = DB::table('leave_applications')->insertGetId([
                'application_no' => $application_no,
                'user_id'       => $user_id,
                'name'          => $leaveName,
                'employee_no'   => $employee_no,
                'leave_id'      => $validatedData['leave_id'],
                'days'          => $days,
                'reason'        => $validatedData['reason'],
                'status'        => 'pending',
                'level'         => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        
            foreach($dates as $date) {
                DB::table('leave_dates')->insertGetId([
                    'leave_application_id' => $applicationID,
                    'date' => $date,
                ]);
            }

            foreach ($approvers as $level => $approverList) {
                foreach ($approverList as $userId) {
                    DB::table('leave_approvals')->insertGetId([
                        'leave_application_id' => $applicationID,
                        'user_id'              => $userId,
                        'level'                => $level,
                        'status'               => 'pending',
                    ]);
                }
            }


            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments', 'public'); // saves in storage/app/public/leave_attachments

                    DB::table('leave_attachments')->insert([
                        'leave_application_id' => $applicationID,
                        'file_path'            => $path,
                        'file_name'            => $file->getClientOriginalName(),
                        'file_type'            => $file->getMimeType(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been submitted',
                'redirect' => route('leaves.create')
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
            return redirect()->route('leaves.index');
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
            $affected = DB::table('leave_applications')
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
            ->editColumn('leave', function($row) {
                return $row->name;
            })
            ->editColumn('name', function($row) {
                return $row->firstname . ' ' . $row->lastname;
            })
            ->addColumn('date', function ($row) {
                return formatDateRanges($row->dates);
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
