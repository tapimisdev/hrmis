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

class LeaveApplicationController extends Controller
{
    protected $employee_service;

    public function __construct(EmployeeService $employee_service)
    {
        $this->employee_service = $employee_service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('leave_applications as la')
                    ->leftJoin('leaves as l', 'la.leave_id', 'l.id')
                    ->select('la.*', 'l.name as leave_name')
                    ->where('la.user_id', Auth::user()->id)
                    ->orderBy('la.created_at', 'desc') // latest first
                    ->get();

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.leave.index');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leaves = DB::table('leaves')->where('is_active', true)->get();

        return view('employee.pages.leave.create', compact('leaves'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveApplication $request) 
    {
        $validatedData = $request->validated();

        if(!empty($validatedData['user_id'])) { // api
            $user = User::with('employeeInformation')->findOrFail($validatedData['user_id']);
            $employee_no = $user->employeeInformation->employee_no;
            $user_id = $user->id;
        } else { // employee side
            $user = Auth::user()->load('employeeInformation');
            $employee_no = $user->toArray()['employee_information']['employee_no'];
            $user_id = Auth::user()->id;
        }

        $organization = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->first();

        DB::beginTransaction();

        try {

            $approvers = DB::table('application_approver')
                ->leftJoin('application_approver_org', 'application_approver.id', '=', 'application_approver_org.application_approver_id')
                ->leftJoin('application_approver_user', 'application_approver.id', '=', 'application_approver_user.application_approver_id')
                ->leftJoin('users', 'application_approver_user.user_id', '=', 'users.id')
                ->where('application_approver.type', 'leave')
                ->where('application_approver_org.division_id', $organization->division_id)
                ->where('application_approver_org.unit_id', $organization->unit_id)
                ->select(
                    'application_approver.*',
                    'application_approver_org.*',
                    'application_approver_user.*',
                    'users.id as user_id',
                    'users.name as user_name',
                )
                ->get();

            if($approvers->isEmpty()) {
                return response([
                    'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
                    'status'  => 'error'
                ], 500); 
            }

            $leaveId = DB::table('leave_applications')->insertGetId([
                'user_id'       => $user_id,
                'employee_no'  => $employee_no,
                'leave_id'      => $validatedData['leave_id'],
                'start_date'    => $validatedData['start_date'],
                'end_date'      => $validatedData['end_date'],
                'reason'        => $validatedData['reason'],
                'status'        => 'pending',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        
            foreach($approvers as $approver) {

                DB::table('application_approvers')->insertGetId([
                    'leave_application_id' => $leaveId,
                    'user_id' => $approver->user_id,
                    'status' => 'pending',
                    'level' => $approver->level,
                ]);

            }


            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments', 'public'); // saves in storage/app/public/leave_attachments

                    DB::table('leave_attachments')->insert([
                        'leave_application_id' => $leaveId,
                        'file_path'            => $path,
                        'file_name'            => $file->getClientOriginalName(),
                        'file_type'            => $file->getMimeType(),
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }

            DB::commit();

            return response([
                'data' => [
                    'leave_id' => $leaveId,
                    'attachments' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
                ],
                'message' => 'Leave application stored successfully'
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
        $leave = DB::table('leave_applications as la')
                    ->leftJoin('leaves as l', 'la.leave_id', '=', 'l.id')
                    ->select('la.*', 'l.name as leave_name')
                    ->where('la.id', $id)
                    ->first();

        $attachments = DB::table('leave_attachments')
                    ->where('leave_application_id', $leave->id)
                    ->get();
                    
        return response(['leave' => $leave, 'attachments' => $attachments, 'status' => 'success'], 200);
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
           ->addColumn('date', function ($row) {
                if ($row->start_date == $row->end_date) {
                    // Single day leave
                    return '<span class="badge rounded-pill bg-primary">'
                            . \Carbon\Carbon::parse($row->start_date)->format('M d, Y') .
                        '</span>';
                } else {
                    // Multi-day leave
                    return '<span class="badge rounded-pill bg-primary me-1">'
                            . \Carbon\Carbon::parse($row->start_date)->format('M d, Y') .
                        '</span>' . 'to ' .
                        '<span class="badge rounded-pill bg-success">'
                            . \Carbon\Carbon::parse($row->end_date)->format('M d, Y') .
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
            ->rawColumns(['actions', 'status', 'date'])
            ->make(true);
    }
}
