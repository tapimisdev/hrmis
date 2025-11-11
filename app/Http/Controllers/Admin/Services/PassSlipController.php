<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PassSlipController extends Controller {

    public function __construct()
    {
        $this->middleware('permission:hr.pass_slip_approval.view')->only(['index', 'show']);
        $this->middleware('permission:hr.pass_slip_approval.save')->only('save');
    }

    public function getRawData(?int $id = null)
    {
        // Fetch main leave applications
        $applications = DB::table('pass_slip_applications as psa')
            ->leftJoin('employee_personal as p', 'psa.employee_no', '=', 'p.employee_no')
            ->leftJoin('leaves as l', 'psa.leave_id', '=', 'l.id')
            ->leftJoin('leave_dates as ld', 'ld.leave_application_id', '=', 'psa.id')
            ->select(
                'p.firstname',
                'p.psastname',
                'psa.id',
                'psa.name',
                'psa.user_id',
                'psa.employee_no',
                'psa.leave_id',
                'psa.days',
                'psa.reason',
                'psa.status',
                'psa.created_at',
                'psa.updated_at',
                'l.name as leave_name',
                DB::raw('GROUP_CONCAT(DISTINCT ld.date ORDER BY ld.date ASC) as dates')
            )
            ->when($id, fn($query) => $query->where('psa.id', $id))
            ->groupBy(
                'psa.id',
                'psa.name',
                'psa.user_id',
                'psa.employee_no',
                'psa.leave_id',
                'psa.days',
                'psa.reason',
                'psa.status',
                'psa.created_at',
                'psa.updated_at',
                'l.name',
                'p.employee_no',
                'p.firstname',
                'p.psastname',
            )
            ->orderByDesc('psa.created_at')
            ->get();

        // Fetch attachments
        $attachments = DB::table('leave_attachments')
            ->select('leave_application_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('leave_application_id', $applications->pluck('id'))
            ->get()
            ->groupBy('leave_application_id');


        // Group approvals by application and level
        $approvalsRaw = DB::table('pass_slip_approvals')
            ->join('employee_information', 'pass_slip_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select([
                'pass_slip_approvals.status',
                'pass_slip_approvals.leave_application_id',
                'pass_slip_approvals.user_id',
                'pass_slip_approvals.level',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.psastname',
            ])
            ->whereIn('pass_slip_approvals.leave_application_id', $applications->pluck('id'))
            ->get();

        $groupedArray = $approvalsRaw
            ->groupBy('level')
            ->map(function ($items) {
                return $items->unique('user_id')->values();
            })
            ->sortKeys()
            ->toArray();

        // Combine all data into results
        $results = $applications->map(function ($item) use ($attachments, $groupedArray) {
            $item->dates = $item->dates ? explode(',', $item->dates) : [];
            $item->attachments = $attachments->get($item->id)?->values() ?? [];
            $item->approvals = $groupedArray;
            return $item;
        });

        return $results;
    }

    public function index() {

        if (request()->ajax()) {
            $query = $this->getRawData();
            return $this->datatable($query);
        }

        return view('admin.pages.services.pass-slip.index');
    }
    
    public function show(int $id) {

        $data = $this->getRawData($id)[0] ?? [];

        if(!$data) {
            return redirect()->back();
        }

        return view('admin.pages.services.pass-slip.show', compact('id', 'data'));
      
    }

    public function rules() {
        return [
            'id' => 'required|exists:pass_slip_applications,id',
            'action' => 'required|in:approve,decline'
        ];
    }

    public function save(int $id, Request $request)
    {
        $payload = $request->all();
        $action = $payload['action'] ?? null;

        switch ($action) {
            case 'approve':
                return $this->approve($id); 
            case 'rejected':
                return $this->decline($id, $payload); 
            default:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid action provided.'
                ], 400);
        }
    }

    public function approve(int $id)
    {
        try {

            DB::table('pass_slip_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id() ?? null
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been approved!',
                'redirect' => route('services.pass-slips.show', ['application' => $id])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function decline(int $id, array $payload)
    {
        try {
            DB::table('pass_slip_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $payload['remarks'] ?? null // Prevents undefined index error
                ]);

            DB::table('pass_slip_approvals')
                ->where('leave_application_id', $id)
                ->update([
                    'status' => 'rejected'
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been rejected!',
                'redirect' => route('services.pass-slips.show', ['application' => $id])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('employee_no', function($row) {
                return $row->employee_no;
            })
            ->editColumn('name', function($row) {
                return $row->firstname . ' ' . $row->psastname;
            })
            ->editColumn('type', function($row) {
                return $row->name;  
            })
            ->editColumn('dates', function ($row) {
                return formatDateRanges($row->dates);
            })
            ->editColumn('status', function ($row) {
                $status = strtolower($row->status);

                $badgeCpsass = match ($status) {
                    'pending'   => 'warning',
                    'approved'  => 'success',
                    'rejected'  => 'dark',
                    'cancelled' => 'danger',
                    default     => 'info',
                };

                return '<span cpsass="badge rounded-pill bg-' . $badgeCpsass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <div cpsass="d-block d-md-flex gap-2 justify-content-start">
                        <a href="'.route('services.pass-slips.show', ['application' => $row->id]).'" 
                            cpsass="btn btn-primary btn show-button ms-1 my-1" 
                            title="Show">
                            <i cpsass="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

}
