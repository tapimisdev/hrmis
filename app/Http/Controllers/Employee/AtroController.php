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

    public function getRawData(?int $id = null)
    {
        $user_id = Auth::id();

        // Fetch main overtime applications with employee name
        $applications = DB::table('overtime_applications as ot')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ot.employee_no')
            ->select(
                'ot.*',
                DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as employee_name")
            )
            ->where('ot.user_id', $user_id)
            ->when($id, function ($query, $id) {
                return $query->where('ot.id', $id);
            })
            ->orderBy('ot.created_at', 'desc')
            ->get();

        $applicationIds = $applications->pluck('id')->toArray();

        if (empty($applicationIds)) {
            // Return empty collection early if no applications found
            return collect();
        }

        // Fetch approvals with employee details
        $approvalsRaw = DB::table('overtime_approvals as oa')
            ->join('employee_information as ei', 'oa.user_id', '=', 'ei.user_id')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select([
                'oa.overtime_applications_id',
                'oa.user_id',
                'oa.level',
                'oa.status',
                'ei.employee_no',
                'ep.firstname',
                'ep.lastname',
            ])
            ->whereIn('oa.overtime_applications_id', $applicationIds)
            ->get();

        // Group approvals by overtime application
        $approvalsByApplication = $approvalsRaw->groupBy('overtime_applications_id');

        // Level-based approval status per overtime application
        $levelApprovals = DB::table('overtime_approvals')
            ->select('overtime_applications_id', 'level', 'status')
            ->whereIn('overtime_applications_id', $applicationIds)
            ->orderBy('level')
            ->get()
            ->groupBy('overtime_applications_id')
            ->map(function ($group) {
                return $group->groupBy('level')->map(function ($levelGroup) {
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
        $results = $applications->map(function ($item) use ($groupedArray, $levelApprovals) {
            $item->approvals = $groupedArray;
            $item->level_approvals = $levelApprovals->get($item->id)?->toArray() ?? [];
            return $item;
        });

        return $results;
    }

    public function getData()
    {
        // Get current user and employee_no
        $user = Auth::user()->load('employeeInformation');
        $employee_no = $user->employeeInformation->employee_no ?? null;

        // Get user's organization info (division and unit)
        $organization = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->latest()
            ->first();

        // If no organization found, return early or empty approvers
        if (!$organization) {
            return [
                'leaves' => collect(),     // assuming you want leaves empty
                'approvers' => collect(),
                'applications' => collect(),
            ];
        }

        // Get pass slip approvers for user's division and unit
        $approvers = DB::table('application_approver')
            ->leftJoin('application_approver_users', 'application_approver.id', '=', 'application_approver_users.application_approver_id')
            ->leftJoin('users', 'application_approver_users.user_id', '=', 'users.id')
            ->where('application_approver.type', 'pass_slip')
            ->where('application_approver.division_id', $organization->division_id)
            ->where('application_approver.unit_id', $organization->unit_id)
            ->select(
                'application_approver_users.level',
                'users.id as user_id',
                'users.name as user_name'
            )
            ->get();

        // Group approvers by level, unique by user id, sorted by level
        $approvers = $approvers
            ->groupBy('level')
            ->mapWithKeys(function ($items, $level) {
                return [
                    $level => $items->unique('user_id')->map(function ($item) {
                        return [
                            'id' => $item->user_id,
                            'name' => $item->user_name,
                        ];
                    })->values()
                ];
            })
            ->sortKeys();

        // Get leave application dates (for current user)
        $leaves = DB::table('leave_applications as la')
            ->join('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
            ->where('la.user_id', $user->id)
            ->select(
                DB::raw("'leave' as title"),
                'la.status',
                'ld.date'
            )
            ->get();

        // Get holidays
        $holidays = DB::table('holidays')
            ->select(
                'name as title',
                DB::raw("'holiday' as status"),
                'date'
            )
            ->get();

        // Get suspensions (active ones only)
        $suspensions = DB::table('suspension as s')
            ->join('suspension_dates as sd', 's.id', '=', 'sd.suspension_id')
            ->where('s.isActive', true)
            ->select(
                's.name as title',
                DB::raw("'suspension' as status"),
                'sd.date'
            )
            ->get();

        // Combine all applications and order by date
        $allApplications = $leaves
            ->concat($holidays)
            ->concat($suspensions)
            ->sortBy('date')
            ->values();

        return [
            'leaves' => $leaves,
            'approvers' => $approvers,
            'applications' => $allApplications,
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

        return view('employee.pages.atro.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $myId = Auth::id();
        $data = $this->getData();
        $approvers = $data['approvers'];
        $approvers = $approvers->map(function ($collection) use ($myId) {
            return $collection->reject(function ($approver) use ($myId) {
                return $approver['id'] === $myId;
            })->values();
        });
        $applications = $data['applications'];

        return view('employee.pages.atro.create', compact('approvers', 'applications'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAtroRequest $request)
    {
        $validatedData = $request->validated();

        $userId = Auth::user()->id;

        DB::beginTransaction();
        try {

            if(empty($validatedData['approvers'])) {
                return response([
                    'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
                    'status'  => 'error'
                ], 500); 
            }

            $employee_no = DB::table('employee_information')->where('user_id', $userId)->value('employee_no');
            $application_no = generateApplicationNo('overtime_applications', 'PSL');
            $approvers = $validatedData['approvers'];

            $atroId = DB::table('overtime_applications')
                    ->insertGetId([
                        'application_no' => $application_no,
                        'user_id' => $userId,
                        'employee_no' => $employee_no,
                        'date' => $validatedData['date'],
                        'start_time' => $validatedData['start_time'],
                        'end_time' => $validatedData['end_time'],
                        'total_hours' => $validatedData['total_hours'],
                        'reason' => $validatedData['reason'],
                        'status' => 'pending',
                        'level' => 1,
                    ]);

            foreach ($approvers as $level => $approverList) {
                foreach ($approverList as $userId) {
                    DB::table('overtime_approvals')->insertGetId([
                        'overtime_application_id' => $atroId,
                        'user_id'              => $userId,
                        'level'                => $level,
                        'status'               => 'pending',
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Overtime application has been submitted',
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
    public function show(string $id)
    {
        $data = $this->getRawData($id)[0] ?? [];

        if(!$data) {
            return redirect()->route('overtime.index');
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
            $affected = DB::table('overtime_applications')
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
            ->editColumn('name', function($row) {
                return $row->employee_name;
            })
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
