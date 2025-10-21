<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ObsApprovalController extends Controller
{

    public function getLevels(bool $forApproval = false, int $id = null)
    {
        $user_id = Auth::id();

        $query = DB::table('obs_approvals')
            ->when(!$forApproval, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            }, function ($query) use ($id) {
                return $query->where('obs_applications_id', $id);
            });

        return $query->distinct()->pluck('level') ?? [];
    }

    public function getRawData(int $level, int $id = null)
    {
        $user_id = Auth::id();

        // Base query for approval data
        $query = DB::table('obs_approvals as oa')
            ->leftJoin('obs_applications as ob', 'ob.id', '=', 'oa.obs_applications_id')
            ->leftJoin('employee_personal as ep', 'ob.employee_no', '=', 'ep.employee_no')
            ->select(
                'ob.id',
                'ob.application_no',
                'ob.user_id',
                'ob.employee_no',
                'ob.date_from',
                'ob.date_to',
                'ob.time_out',
                'ob.time_in',
                'ob.destination',
                'ob.purpose',
                'ob.mode_of_transport',
                'ob.estimated_expense',
                'ob.charge_to',
                'ob.status',
                'ob.remarks',
                'ob.level',
                'ob.created_at',
                'ob.approved_at',
                'ob.approval_remarks',
                'ep.firstname',
                'ep.lastname',
            )
            ->where('oa.user_id', $user_id)
            ->where('oa.level', $level)
            ->whereColumn('oa.level', 'ob.level');

        // If a specific application ID is provided
        if ($id !== null) {
            $query->where('ob.id', $id);
            $item = $query->first();

            if (!$item) {
                return null;
            }

            $attachments = DB::table('obs_attachments')
                ->where('obs_applications_id', $item->id)
                ->get();

            return (object)[
                'id' => $item->id,
                'application_no' => $item->application_no,
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'user_id' => $item->user_id,
                'employee_no' => $item->employee_no,
                'date_from' => $item->date_from,
                'date_to' => $item->date_to,
                'time_out' => $item->time_out,
                'time_in' => $item->time_in,
                'destination' => $item->destination,
                'purpose' => $item->purpose,
                'mode_of_transport' => $item->mode_of_transport,
                'estimated_expense' => $item->estimated_expense,
                'charge_to' => $item->charge_to,
                'status' => $item->status,
                'remarks' => $item->remarks,
                'level' => $item->level,
                'created_at' => $item->created_at,
                'approved_at' => $item->approved_at,
                'approval_remarks' => $item->approval_remarks,
                'attachments' => $attachments,
            ];
        }

        // Fetch all matching records
        $data = $query->get();

        // Collect all application IDs in one go to avoid multiple queries
        $applicationIds = $data->pluck('id');

        $allAttachments = DB::table('obs_attachments')
            ->whereIn('obs_applications_id', $applicationIds)
            ->get()
            ->groupBy('obs_applications_id');

        return $data->map(function ($item) use ($allAttachments) {
            return (object)[
                'id' => $item->id,
                'application_no' => $item->application_no,
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'user_id' => $item->user_id,
                'employee_no' => $item->employee_no,
                'date_from' => $item->date_from,
                'date_to' => $item->date_to,
                'time_out' => $item->time_out,
                'time_in' => $item->time_in,
                'destination' => $item->destination,
                'purpose' => $item->purpose,
                'mode_of_transport' => $item->mode_of_transport,
                'estimated_expense' => $item->estimated_expense,
                'charge_to' => $item->charge_to,
                'status' => $item->status,
                'remarks' => $item->remarks,
                'level' => $item->level,
                'created_at' => $item->created_at,
                'approved_at' => $item->approved_at,
                'attachments' => $allAttachments->get($item->id)?->values() ?? [],
            ];
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(?int $level = null)
    {

        $levels = $this->getLevels(false)->toArray();

        if (empty($levels)) {
            if ($level !== null) {
                return redirect()->route('approval-obs.index');
            }
            return $this->handleRequest($level, $levels);
        }

        if ($level === null || !in_array($level, $levels)) {
            return redirect()->route('approval-obs.index', ['level' => $levels[0]]);
        }

        return $this->handleRequest($level, $levels);

    }

    protected function handleRequest(?int $level, array $levels)
    {
        if (request()->ajax()) {
            $data = $level !== null ? $this->getRawData($level) ?? [] : [];
            return $this->datatable($level, $data);
        }

        return view('employee.pages.obs-approval.index', compact('levels', 'level'));
    }

    public function show(int $level, int $id) {

        $data = $this->getRawData($level, $id) ?? [];

        if(!$data) {
            return redirect()->back();
        }

        return view('employee.pages.obs-approval.show', compact('id', 'data'));
    }


    public function save(int $level, int $id, Request $request)
    {

        $payload = $request->all();
        $action = $payload['action'] ?? null;
        $query = DB::table('obs_applications')
            ->where('id', $id);
        
        if(!$query->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to find this application in the system',
                'redirect' => ''
            ]);
        }

        switch ($action) {
            case 'approve':
                return $this->approve($level, $id); 
            case 'rejected':
                return $this->rejected($level, $id, $payload); 
            default:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid action provided.'
                ], 400);
        }
    }

    public function approve(int $level, int $id)
    {
        try {

            $allLevels = $this->getLevels(true, $id)->toArray() ?? [];
            $maxLevel = max($allLevels);
            $user_id = Auth::id();

            DB::table('obs_approvals')
                ->where('obs_applications_id', $id)
                ->where('user_id', $user_id)
                ->where('level', $level)
                ->update([
                    'status' => 'approved'
                ]);
            
            if($level == $maxLevel) {
                DB::table('obs_applications')
                    ->where('id', $id)
                    ->update([
                        'status' => 'approved'
                    ]);
            }
            
            if($level < $maxLevel) {
                $nextLevel = $level += 1;
                DB::table('obs_applications')
                    ->where('id', $id)
                    ->update([
                        'level' => $nextLevel
                    ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been approved!',
                'redirect' => route('approval-obs.index', ['level' => $level])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejected(int $level, int $id, array $payload)
    {
        try {
            $allLevels = $this->getLevels(true, $id)->toArray() ?? [];
            $maxLevel = max($allLevels);
            $remarks = $payload['remarks'] ?? null;
            $user_id = Auth::id();

            for ($i = $level; $i <= $maxLevel; $i++) {
                $query = DB::table('obs_approvals')
                    ->where('obs_applications_id', $id)
                    ->where('level', $i);

                if ($i === $level) {
                    $query->where('user_id', $user_id)->update([
                        'status' => 'rejected',
                        'approval_remarks' => $remarks,
                    ]);
                } else {
                    $query->update([
                        'status' => 'rejected',
                        'approval_remarks' => null,
                    ]);
                }
            }

            DB::table('obs_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $remarks,
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been rejected!',
                'redirect' => route('approval-obs.index', ['level' => $level])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function datatable($level, $query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                return $row->firstname . ' ' . $row->lastname ?? 'N/A';
            })
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
                ->addColumn('actions', function ($row) use ($level) {
                    $buttons = '
                        <div class="d-flex align-items-center">
                            <a href="'.route('approval-obs.show', ['level' => $level, 'id' => $row->id]).'" 
                                class="btn btn-outline-primary btn show-button ms-1 my-1" 
                                title="Show">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                    ';
                    $buttons .= '</div>';

                    return $buttons;
                })
                ->rawColumns(['actions', 'status', 'date_range'])
                ->make(true);
    }

}
