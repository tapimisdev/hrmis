<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class OvertimeController extends Controller {

    public function getRawData(?int $id = null)
    {
        // Fetch main leave applications
        $applications = DB::table('overtime_applications as ota')
            ->leftJoin('employee_personal as p', 'ota.employee_no', '=', 'p.employee_no')
            ->leftJoin('leaves as l', 'ota.overtime_id', '=', 'l.id')
            ->leftJoin('overtime_dates as ld', 'ld.overtime_approvals_id', '=', 'ota.id')
            ->select(
                'p.firstname',
                'p.otastname',
                'ota.id',
                'ota.name',
                'ota.user_id',
                'ota.employee_no',
                'ota.overtime_id',
                'ota.days',
                'ota.reason',
                'ota.status',
                'ota.created_at',
                'ota.updated_at',
                'l.name as overtime_name',
                DB::raw('GROUP_CONCAT(DISTINCT ld.date ORDER BY ld.date ASC) as dates')
            )
            ->when($id, fn($query) => $query->where('ota.id', $id))
            ->groupBy(
                'ota.id',
                'ota.name',
                'ota.user_id',
                'ota.employee_no',
                'ota.overtime_id',
                'ota.days',
                'ota.reason',
                'ota.status',
                'ota.created_at',
                'ota.updated_at',
                'l.name',
                'p.employee_no',
                'p.firstname',
                'p.otastname',
            )
            ->orderByDesc('ota.created_at')
            ->get();

        // Fetch attachments
        $attachments = DB::table('overtime_attachments')
            ->select('overtime_approvals_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('overtime_approvals_id', $applications->pluck('id'))
            ->get()
            ->groupBy('overtime_approvals_id');


        // Group approvals by application and level
        $approvalsRaw = DB::table('overtime_approvals')
            ->join('employee_information', 'overtime_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select([
                'overtime_approvals.status',
                'overtime_approvals.overtime_approvals_id',
                'overtime_approvals.user_id',
                'overtime_approvals.level',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.otastname',
            ])
            ->whereIn('overtime_approvals.overtime_approvals_id', $applications->pluck('id'))
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

        return view('admin.pages.services.overtime.index');
    }
    
    public function show(int $id) {

        $data = $this->getRawData($id)[0] ?? [];

        if(!$data) {
            return redirect()->back();
        }

        return view('admin.pages.services.overtime.show', compact('id', 'data'));
      
    }

    public function rules() {
        return [
            'id' => 'required|exists:overtime_applications,id',
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

            DB::table('overtime_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id() ?? null
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been approved!',
                'redirect' => route('services.overtimes.show', ['application' => $id])
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
            DB::table('overtime_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $payload['remarks'] ?? null // Prevents undefined index error
                ]);

            DB::table('overtime_approvals')
                ->where('overtime_approvals_id', $id)
                ->update([
                    'status' => 'rejected'
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been rejected!',
                'redirect' => route('services.overtimes.show', ['application' => $id])
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
                return $row->firstname . ' ' . $row->otastname;
            })
            ->editColumn('type', function($row) {
                return $row->name;  
            })
            ->editColumn('dates', function ($row) {
                return formatDateRanges($row->dates);
            })
            ->editColumn('status', function ($row) {
                $status = strtolower($row->status);

                $badgeCotass = match ($status) {
                    'pending'   => 'warning',
                    'approved'  => 'success',
                    'rejected'  => 'dark',
                    'cancelled' => 'danger',
                    default     => 'info',
                };

                return '<span cotass="badge rounded-pill bg-' . $badgeCotass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <div cotass="d-block d-md-flex gap-2 justify-content-start">
                        <a href="'.route('services.overtimes.show', ['application' => $row->id]).'" 
                            cotass="btn btn-outline-primary btn show-button ms-1 my-1" 
                            title="Show">
                            <i cotass="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

}
