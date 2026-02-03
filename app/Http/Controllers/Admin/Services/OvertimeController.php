<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use Carbon\Carbon;

class OvertimeController extends Controller {


    protected $EventService;

    public function __construct(EventService $EventService)
    {
        $this->middleware('permission:hr.overtime_approval.view')->only(['index', 'show']);
        $this->middleware('permission:hr.overtime_approval.save')->only('save');
        $this->EventService = $EventService;
    }

    public function getRawData(?int $id = null)
    {
        /*
        |--------------------------------------------------------------------------
        | Main Overtime Applications
        |--------------------------------------------------------------------------
        */
        $applications = DB::table('overtime_applications as oa')
            ->leftJoin('employee_personal as p', 'oa.employee_no', '=', 'p.employee_no')
            ->select(
                'oa.id',
                'oa.application_no',
                'oa.user_id',
                'oa.employee_no',
                'oa.date',
                'oa.start_time',
                'oa.end_time',
                'oa.total_hours',
                'oa.reason',
                'oa.status',
                'oa.remarks',
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
        $attachments = DB::table('overtime_attachments')
            ->select(
                'overtime_applications_id',
                'file_name',
                'file_path',
                'file_type'
            )
            ->whereIn('overtime_applications_id', $applicationIds)
            ->get()
            ->groupBy('overtime_applications_id');

        /*
        |--------------------------------------------------------------------------
        | Approvals
        |--------------------------------------------------------------------------
        */
        $approvalsRaw = DB::table('overtime_approvals as oa')
            ->join('employee_information as ei', 'oa.user_id', '=', 'ei.user_id')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select(
                'oa.overtime_applications_id',
                'oa.user_id',
                'oa.level',
                'oa.status',
                'ep.firstname',
                'ep.lastname'
            )
            ->whereIn('oa.overtime_applications_id', $applicationIds)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Group approvals PER application → PER level (UI-friendly)
        |--------------------------------------------------------------------------
        */
        $approvalsByApplication = $approvalsRaw
            ->groupBy('overtime_applications_id')
            ->map(function ($items) {
                return $items
                    ->groupBy('level')
                    ->map(fn ($levelItems) =>
                        $levelItems->unique('user_id')->values()
                    )
                    ->sortKeys()
                    ->values();
            });

        /*
        |--------------------------------------------------------------------------
        | Merge Data
        |--------------------------------------------------------------------------
        */
        return $applications->map(function ($item) use ($attachments, $approvalsByApplication) {
            $item->attachments = $attachments->get($item->id, collect())->values();
            $item->approvals   = $approvalsByApplication->get($item->id, collect());

            return $item;
        });
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

            $existingData = DB::table('overtime_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('overtime_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id() ?? null
                ]);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your overtime application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/overtime'
            ];
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime application has been approved!',
                'redirect' => route('services.overtime.show', ['application' => $id])
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

            $existingData = DB::table('overtime_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('overtime_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $payload['remarks'] ?? null // Prevents undefined index error
                ]);

            DB::table('overtime_approvals')
                ->where('overtime_applications_id', $id)
                ->update([
                    'status' => 'rejected'
                ]);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'rejected',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has rejected your overtime application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/overtime'
            ];
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime application has been rejected!',
                'redirect' => route('services.overtime.show', ['application' => $id])
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

            ->editColumn('name', function ($row) {
                return trim($row->firstname . ' ' . $row->lastname);
            })
            ->editColumn('date_time', function ($row) {
                    if (!$row->date) {
                        return '';
                    }

                    $date = \Carbon\Carbon::parse($row->date)->format('M d, Y');

                    $time = '';
                    if ($row->start_time && $row->end_time) {
                        $time = \Carbon\Carbon::parse($row->start_time)->format('h:i A')
                            . ' ~ '
                            . \Carbon\Carbon::parse($row->end_time)->format('h:i A');
                    }

                    return trim($date . ' ( ' . $time . ') ');
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
                        <a href="' . route('services.overtime.show', ['application' => $row->id]) . '"
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
