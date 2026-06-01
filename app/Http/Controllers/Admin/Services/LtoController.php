<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LtoController extends Controller
{
    protected $EventService;

    public function __construct(EventService $EventService)
    {
        $this->middleware('permission:hr.lto_approval.view')->only(['index', 'show']);
        $this->middleware('permission:hr.lto_approval.save')->only('save');
        $this->EventService = $EventService;
    }

    public function getRawData(?int $id = null)
    {
        $applications = DB::table('lto_applications as la')
            ->leftJoin('employee_personal as p', 'la.employee_no', '=', 'p.employee_no')
            ->leftJoin('lto_dates as ld', 'ld.lto_application_id', '=', 'la.id')
            ->select(
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.lto_no',
                'la.isHazardous',
                'la.status',
                'la.remarks',
                'la.created_at',
                'la.updated_at',
                'p.firstname',
                'p.lastname',
                DB::raw("
                    GROUP_CONCAT(
                        DISTINCT CONCAT(ld.date, '|', ld.shift)
                        ORDER BY ld.date ASC
                        SEPARATOR ','
                    ) as dates
                ")
            )
            ->when($id, fn ($q) => $q->where('la.id', $id))
            ->groupBy(
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.lto_no',
                'la.isHazardous',
                'la.status',
                'la.remarks',
                'la.created_at',
                'la.updated_at',
                'p.firstname',
                'p.lastname'
            )
            ->orderByDesc('la.created_at')
            ->get();

        if ($applications->isEmpty()) {
            return collect();
        }

        $applicationIds = $applications->pluck('id');

        $attachments = DB::table('lto_attachments')
            ->select(
                'lto_application_id',
                'file_name',
                'file_path',
                'file_type'
            )
            ->whereIn('lto_application_id', $applicationIds)
            ->get()
            ->groupBy('lto_application_id');

        $approvals = DB::table('lto_approvals as la')
            ->join('employee_information as ei', 'la.user_id', '=', 'ei.user_id')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select(
                'la.lto_application_id',
                'la.user_id',
                'la.level',
                'la.status',
                'ep.firstname',
                'ep.lastname'
            )
            ->whereIn('la.lto_application_id', $applicationIds)
            ->get()
            ->groupBy('lto_application_id')
            ->map(function ($items) {
                return $items
                    ->groupBy('level')
                    ->map(fn ($lvl) => $lvl->unique('user_id')->values())
                    ->sortKeys();
            });

        return $applications->map(function ($item) use ($attachments, $approvals) {
            $item->dates = $item->dates
                ? collect(explode(',', $item->dates))
                    ->map(function ($entry) {
                        [$date, $shift] = array_pad(explode('|', $entry), 2, null);

                        return [
                            'date' => $date,
                            'shift' => $shift,
                        ];
                    })
                    ->values()
                : collect();

            $item->attachments = $attachments->get($item->id)?->values() ?? collect();
            $item->approvals = $approvals->get($item->id) ?? collect();

            return $item;
        });
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->datatable($this->getRawData());
        }

        return view('admin.pages.services.lto.index');
    }

    public function show(int $id)
    {
        $data = $this->getRawData($id)[0] ?? [];

        if (!$data) {
            return redirect()->back();
        }

        return view('admin.pages.services.lto.show', compact('id', 'data'));
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
            $existingData = DB::table('lto_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('lto_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'actioned_by' => Auth::id() ?? null
                ]);

            $sender = ucwords(Auth::user()->name);
            $applicationNo = $existingData->lto_no;
            $this->EventService->pushNotification([
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $existingData->user_id,
                'message' => '%b' . $sender . '%b has approved your local travel order application (%bi' . strtoupper($applicationNo) . ') %bi',
                'link' => '/employee/local-travel-order?show=true&id=' . $existingData->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Local travel order application has been approved!',
                'redirect' => route('services.lto.show', ['application' => $id])
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
            $existingData = DB::table('lto_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('lto_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'actioned_by' => Auth::id() ?? null,
                    'remarks' => $payload['remarks'] ?? null
                ]);

            DB::table('lto_approvals')
                ->where('lto_application_id', $id)
                ->update([
                    'status' => 'rejected'
                ]);

            $sender = ucwords(Auth::user()->name);
            $applicationNo = $existingData->lto_no;
            $this->EventService->pushNotification([
                'type' => 'rejected',
                'sender' => $sender,
                'receiver' => $existingData->user_id,
                'message' => '%b' . $sender . '%b has rejected your local travel order application (%bi' . strtoupper($applicationNo) . ') %bi',
                'link' => '/employee/local-travel-order?show=true&id=' . $existingData->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Local travel order application has been rejected!',
                'redirect' => route('services.lto.show', ['application' => $id])
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
            ->editColumn('employee_no', fn ($row) => $row->employee_no)
            ->addColumn('name', fn ($row) => trim($row->firstname . ' ' . $row->lastname))
            ->addColumn('date', fn ($row) => formatDateRanges($row->dates->pluck('date')->implode('|')))
            ->editColumn('status', function ($row) {
                $status = strtolower($row->status);

                $badgeClass = match ($status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'dark',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };

                return '<span class="badge rounded-pill bg-' . $badgeClass . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <div class="d-flex gap-2">
                        <a href="' . route('services.lto.show', ['application' => $row->id]) . '"
                        class="btn btn-primary btn-sm"
                        title="View">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['date', 'status', 'actions'])
            ->make(true);
    }
}
