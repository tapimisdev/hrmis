<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PassSlipController extends Controller {


    protected $EventService;

    public function __construct(EventService $EventService)
    {
        $this->middleware('permission:hr.pass_slip_approval.view')->only(['index', 'show']);
        $this->middleware('permission:hr.pass_slip_approval.save')->only('save');
        $this->EventService = $EventService;
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
            ->leftJoin('obs_dates as od', 'od.obs_application_id', '=', 'oa.id')
            ->select(
                'oa.id',
                'oa.application_no',
                'oa.name',
                'oa.user_id',
                'oa.employee_no',
                'oa.reason',
                'oa.status',
                'oa.remarks',
                'oa.created_at',
                'oa.updated_at',
                'p.firstname',
                'p.lastname',
                DB::raw("
                    GROUP_CONCAT(
                        DISTINCT CONCAT(od.date, '|', od.shift)
                        ORDER BY od.date ASC
                        SEPARATOR ','
                    ) as dates
                ")
            )
            ->when($id, fn ($q) => $q->where('oa.id', $id))
            ->groupBy(
                'oa.id',
                'oa.application_no',
                'oa.name',
                'oa.user_id',
                'oa.employee_no',
                'oa.reason',
                'oa.status',
                'oa.remarks',
                'oa.created_at',
                'oa.updated_at',
                'p.firstname',
                'p.lastname'
            )
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
        | Approvals (Grouped per Application → per Level)
        |--------------------------------------------------------------------------
        */
        $approvals = DB::table('obs_approvals as oa')
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
            ->get()
            ->groupBy('obs_applications_id')
            ->map(function ($items) {
                return $items
                    ->groupBy('level')
                    ->map(fn ($lvl) => $lvl->unique('user_id')->values())
                    ->sortKeys();
            });

        /*
        |--------------------------------------------------------------------------
        | Merge Everything
        |--------------------------------------------------------------------------
        */
        return $applications->map(function ($item) use ($attachments, $approvals) {

            /*
            |----------------------------------------------------------
            | Convert dates string → structured array
            |----------------------------------------------------------
            */
            $item->dates = $item->dates
                ? collect(explode(',', $item->dates))
                    ->map(function ($entry) {
                        [$date, $shift] = array_pad(explode('|', $entry), 2, null);

                        return [
                            'date'  => $date,
                            'shift' => $shift,
                        ];
                    })
                    ->values()
                : collect();

            /*
            |----------------------------------------------------------
            | Attachments
            |----------------------------------------------------------
            */
            $item->attachments = $attachments->get($item->id)?->values() ?? collect();

            /*
            |----------------------------------------------------------
            | Approvals per application
            |----------------------------------------------------------
            */
            $item->approvals = $approvals->get($item->id) ?? collect();

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

            $existingData = DB::table('obs_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('obs_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'actioned_by' => Auth::id() ?? null
                ]);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your pass slip application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/pass-slip?show=true&id=' . $existingData->id
            ];
            $this->EventService->pushNotification($payload);

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

            $existingData = DB::table('obs_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('obs_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'actioned_by' => Auth::id() ?? null,
                    'remarks' => $payload['remarks'] ?? null 
                ]);

            DB::table('obs_approvals')
                ->where('obs_applications_id', $id)
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
                'message' => '%b' . $sender . '%b has rejected your pass slip application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/pass-slip?show=true&id=' . $existingData->id
            ];
            
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been rejected!',
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
