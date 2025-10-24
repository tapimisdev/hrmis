<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Services\ApprovalsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ObsApprovalController extends Controller
{

    protected $approvalService;

    public function __construct(ApprovalsController $ApprovalsController) {
        $this->approvalService = $ApprovalsController;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(?int $level = null)
    {

        $levels = $this->approvalService->getLevels('pass_slip', false)->toArray();

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
            $data = $level !== null ? $this->approvalService->getData('pass_slip', $level, null, true) ?? [] : [];
            return $this->datatable($level, $data);
        }

        return view('employee.pages.obs-approval.index', compact('levels', 'level'));
    }

    public function show($level, int $id) {

        $data = $this->approvalService->getData('pass_slip', $level, $id) ?? [];

        if(!$data) {
            return redirect()->back();
        }

        return view('employee.pages.obs-approval.show', compact('id', 'data'));
    }

    public function view($level = null) {

        if (request()->ajax()) {
            $data = $level !== null ? $this->approvalService->getData('pass_slip', $level) ?? [] : [];
            return $this->datatable($level, $data);
        }

        return view('employee.pages.obs-approval.view', compact('level'));
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

            $allLevels = $this->approvalService->getLevels('pass_slip', true, $id)->toArray() ?? [];
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
            $allLevels = $this->approvalService->getLevels('pass_slip', true, $id)->toArray() ?? [];
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
                return $row->firstname . ' ' . $row->lastname;
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
