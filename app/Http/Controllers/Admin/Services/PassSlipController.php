<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
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
        /*
        |--------------------------------------------------------------------------
        | Main OBS Applications
        |--------------------------------------------------------------------------
        */
        $applications = DB::table('obs_applications as oa')
            ->leftJoin('employee_personal as p', 'oa.employee_no', '=', 'p.employee_no')
            ->select(
                'oa.id',
                'oa.application_no',
                'oa.user_id',
                'oa.employee_no',
                'oa.date_from',
                'oa.date_to',
                'oa.time_out',
                'oa.time_in',
                'oa.destination',
                'oa.purpose',
                'oa.mode_of_transport',
                'oa.estimated_expense',
                'oa.charge_to',
                'oa.status',
                'oa.remarks',
                'oa.approval_remarks',
                'oa.approved_at',
                'oa.created_at',
                'oa.updated_at',
                'p.firstname',
                'p.lastname'
            )
            ->when($id, fn ($q) => $q->where('oa.id', $id))
            ->orderByDesc('oa.created_at')
            ->get();

        if ($applications->isEmpty()) {
            return collect();
        }

        $applicationIds = $applications->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        */
        $attachments = DB::table('obs_attachments')
            ->select(
                'obs_applications_id',
                'file_name',
                'file_path',
                'file_type'
            )
            ->whereIn('obs_applications_id', $applicationIds)
            ->get()
            ->groupBy('obs_applications_id');

        /*
        |--------------------------------------------------------------------------
        | Approvals
        |--------------------------------------------------------------------------
        */
        $approvalsRaw = DB::table('obs_approvals as oa')
            ->join('employee_information as ei', 'oa.user_id', '=', 'ei.user_id')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select(
                'oa.obs_applications_id',
                'oa.user_id',
                'oa.level',
                'oa.status',
                'ep.firstname',
                'ep.lastname'
            )
            ->whereIn('oa.obs_applications_id', $applicationIds)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Group approvals by level (UI display)
        |--------------------------------------------------------------------------
        */
        $groupedApprovals = $approvalsRaw
            ->groupBy('level')
            ->map(fn ($items) => $items->unique('user_id')->values())
            ->sortKeys()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Merge All Data
        |--------------------------------------------------------------------------
        */
        return $applications->map(function ($item) use ($attachments, $groupedApprovals) {
            $item->attachments = $attachments->get($item->id)?->values() ?? [];
            $item->approvals = $groupedApprovals;
            return $item;
        });
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

        // dd($data);

        if(!$data) {
            return redirect()->back();
        }

        return view('admin.pages.services.pass-slip.show', compact('id', 'data'));
      
    }

    public function rules() {
        return [
            'id' => 'required|exists:obs_applications,id',
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

            DB::table('obs_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id() ?? null
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been approved!',
                'redirect' => route('services.pass_slip.show', ['application' => $id])
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
            DB::table('obs_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $payload['remarks'] ?? null // Prevents undefined index error
                ]);

            DB::table('obs_approvals')
                ->where('obs_applications_id', $id)
                ->update([
                    'status' => 'rejected'
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'PAss slip application has been rejected!',
                'redirect' => route('services.pass_slip.show', ['application' => $id])
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

            ->editColumn('employee_no', function ($row) {
                return $row->employee_no;
            })

            ->addColumn('name', function ($row) {
                return trim($row->firstname . ' ' . $row->lastname);
            })

            ->addColumn('date', function ($row) {
                if (!$row->date_from || !$row->date_to) {
                    return '';
                }

                return formatDateRanges($row->date_from) . ' - ' . formatDateRanges($row->date_to);
            })

            ->editColumn('status', function ($row) {
                $status = strtolower($row->status);

                $badgeClass = match ($status) {
                    'pending'   => 'warning',
                    'approved'  => 'success',
                    'rejected'  => 'dark',
                    'cancelled' => 'danger',
                    default     => 'secondary',
                };

                return '<span class="badge rounded-pill bg-' . $badgeClass . '">' 
                    . ucfirst($status) . 
                '</span>';
            })

            ->addColumn('actions', function ($row) {
                return '
                    <div class="d-flex gap-2">
                        <a href="' . route('services.pass_slip.show', ['application' => $row->id]) . '"
                        class="btn btn-primary btn-sm"
                        title="View">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })

            ->rawColumns(['status', 'actions'])
            ->make(true);
    }


}
