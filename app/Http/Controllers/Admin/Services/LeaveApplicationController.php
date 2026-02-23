<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeaveApplicationController extends Controller {

    protected $employeeService;
    protected $generateService;
    protected $EventService;

    public function __construct(EmployeeService $employeeService, EventService $EventService)
    {
        $this->middleware('permission:hr.leave_approval.view')->only(['view', 'show']);
        $this->middleware('permission:hr.leave_approval.save')->only('save');
        $this->employeeService = $employeeService;
        $this->EventService = $EventService;
    }

    public function getRawData(?int $id = null)
    {
        $applications = DB::table('leave_applications as la')
            ->leftJoin('employee_personal as p', 'la.employee_no', '=', 'p.employee_no')
            ->leftJoin('leaves as l', 'la.leave_id', '=', 'l.id')
            ->leftJoin('leave_dates as ld', 'ld.leave_application_id', '=', 'la.id')
            ->where('ld.isActive', true)
            ->select(
                'p.firstname',
                'p.lastname',
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.leave_id',
                'la.credit_equivalent',
                'la.reason',
                'la.status',
                'la.created_at',
                'la.updated_at',
                'l.name as leave_name',
                DB::raw("
                    GROUP_CONCAT(
                        DISTINCT CONCAT(ld.date, '|', ld.shift)
                        ORDER BY ld.date ASC
                    ) as dates
                ")
            )
            ->when($id, fn ($query) => $query->where('la.id', $id))
            ->groupBy(
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.leave_id',
                'la.credit_equivalent',
                'la.reason',
                'la.status',
                'la.created_at',
                'la.updated_at',
                'l.name',
                'p.firstname',
                'p.lastname'
            )
            ->orderByDesc('la.created_at')
            ->get();

        // Attachments
        $attachments = DB::table('leave_attachments')
            ->select('leave_application_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('leave_application_id', $applications->pluck('id'))
            ->get()
            ->groupBy('leave_application_id');

        // Approvals
        $approvalsRaw = DB::table('leave_approvals')
            ->join('employee_information', 'leave_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select(
                'leave_approvals.status',
                'leave_approvals.leave_application_id',
                'leave_approvals.user_id',
                'leave_approvals.level',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.lastname'
            )
            ->whereIn('leave_approvals.leave_application_id', $applications->pluck('id'))
            ->get();

        $groupedApprovals = $approvalsRaw
            ->groupBy('level')
            ->map(fn ($items) => $items->unique('user_id')->values())
            ->sortKeys()
            ->values();

        // Final mapping
        $results = $applications->map(function ($item) use ($attachments, $groupedApprovals) {

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
                : [];

            $item->attachments = $attachments->get($item->id)?->values() ?? [];
            $item->approvals = $groupedApprovals;

            return $item;
        });

        return $results;
    }

    public function index() 
    {
        if (request()->ajax()) {
            $query = $this->getRawData();
            return $this->datatable($query);
        }

        return view('admin.pages.services.leave.index');
    }
    
    public function show(int $id) 
    {

        $data = $this->getRawData($id)[0] ?? [];

        if(!$data) {
            return redirect()->back();
        }

        $employee_no = $data->employee_no;
        $leave_id = $data->leave_id;
        $currentMonth = Carbon::now()->format('Y-m');

        $leaveSetting = $this->employeeService->getLeaveSettings($leave_id);

        $deductionLeaveId = $leaveSetting->deduct_credit_id ?? null;
        $showBreakdown = true;
        
        if(is_null($deductionLeaveId)) {
            $showBreakdown = false;
        }

        $latestCredits = $this->employeeService->getLeaveCreditsByMonthYear($employee_no, $deductionLeaveId ?? 0, $currentMonth);
        $leaveDeductInfo = $this->employeeService->getLeaveInfo($deductionLeaveId);
       
        $remaining_balance = (float) $latestCredits['current']?->balance ?? 0;
        $toBeDeducted = (float) $this->compute($data->dates);

        $toBeDeductedFromCredits = $leaveDeductInfo;
        $new_balance = $remaining_balance - $toBeDeducted;

        $hasBalance = false;

        if($toBeDeducted > $remaining_balance) {
            $hasBalance = false;
        } else {
            $hasBalance = true;
        }

        $computation = [
            'showBreakdown' => $showBreakdown,
            'remaining_balance' => number_format($remaining_balance ?? 0, 2),
            'deduction' => number_format($toBeDeducted ?? 0, 2),
            'new_balance' => number_format($new_balance ?? 0, 2),
            'toBeDeductedFromCredits' => $toBeDeductedFromCredits ?? 'N/A',
        ];

        return view('admin.pages.services.leave.show', compact('employee_no', 'id', 'data', 'hasBalance', 'computation'));
      
    }

    public function compute($dates): float
    {
        return $dates->sum(function ($item) {
            return match (strtolower($item['shift'] ?? '')) {
                'morning', 'afternoon' => 0.5,
                'wholeday' => 1.0,
                default => 0.0,
            };
        });
    }

    public function rules() 
    {
        return [
            'id' => 'required|exists:leave_applications,id',
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

        DB::beginTransaction();

        try {

            $existingData = DB::table('leave_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            $updateCredits = $this->updateCredits($id);

            if($updateCredits['status'] !== 'success') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $updateCredits['message']
                ], 500);
            }

            DB::table('leave_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'credit_remarks' => $updateCredits['single_remarks'] ?? null,
                    'approver_id' => Auth::id() ?? null
                ]);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your leave application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/leaves'
            ];
            $this->EventService->pushNotification($payload);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been approved!',
                'redirect' => route('services.leaves.show', ['application' => $id])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function decline(int $id, array $payload)
    {
        try {

            $existingData = DB::table('leave_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('leave_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'remarks' => $payload['remarks'] ?? null
                ]);

            DB::table('leave_approvals')
                ->where('leave_application_id', $id)
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
                'message' => '%b' . $sender . '%b has rejected your leave application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/leaves'
            ];
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application has been rejected!',
                'redirect' => route('services.leaves.show', ['application' => $id])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCredits(int $id)
    {
        $data = $this->getRawData($id)->first();

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => "Leave application not found: {$id}",
            ], 404);
        }

        $employee_no  = $data->employee_no;
        $leave_id     = $data->leave_id;
        $leaveName    = $data->name;
        $currentMonth = now()->format('Y-m');

        $leaveSetting = DB::table('leaves_settings')
            ->where('leave_id', $leave_id)
            ->first();

        $deductionLeaveId = $leaveSetting->deduct_credit_id ?? null;

        $remaining_balance = 0;

        if ($deductionLeaveId) {
            $latestCredits = $this->employeeService
                ->getLeaveCreditsByMonthYear($employee_no, $deductionLeaveId, $currentMonth);

            $remaining_balance = (float) ($latestCredits['current']->balance ?? 0);
        }

        $datesByMonth = [];
        $toBeDeducted = 0;

        foreach ($data->dates as $d) {
            $dateObj = Carbon::parse($d['date']);
            $month   = strtoupper($dateObj->format('M'));
            $day     = $dateObj->format('j');
            $shift   = strtolower($d['shift']);

            $credit = match ($shift) {
                'morning', 'afternoon' => 0.5,
                'wholeday'             => 1.0,
                default                => 0,
            };

            if ($deductionLeaveId) {
                $toBeDeducted += $credit;
            }

            $datesByMonth[$month][] = [
                'day'    => $day,
                'shift'  => $shift,
                'credit' => $credit,
            ];
        }

        $new_balance = $remaining_balance - $toBeDeducted;

        $singleRemark = collect($datesByMonth)
            ->map(function ($days, $month) use ($leave_id, $leaveName, $deductionLeaveId) {

                $dayStrings  = [];
                $totalCredit = 0;

                foreach ($days as $d) {

                    $shiftShort = match ($d['shift']) {
                        'morning'   => 'AM',
                        'afternoon' => 'PM',
                        'wholeday'  => 'WD',
                        default     => $d['shift'],
                    };

                    $dayStrings[] = "{$month} {$d['day']} ({$shiftShort})";

                    $totalCredit += $d['credit'];
                }

                if ($leave_id && is_null($deductionLeaveId)) {
                    return sprintf(
                        "%s [%s]",
                        implode(', ', $dayStrings),
                        $leaveName
                    );
                }

                if ($leave_id == $deductionLeaveId) {
                    return sprintf(
                        "%s (Eqv: %.2f)",
                        implode(', ', $dayStrings),
                        $totalCredit
                    );
                }

                return sprintf(
                    "%s (Eqv: %.2f) [%s]",
                    implode(', ', $dayStrings),
                    $totalCredit,
                    $leaveName
                );
            })
            ->implode(' | ');

        $effectiveLeaveId = $deductionLeaveId ?? $leave_id;
        $combinedRemarks = $singleRemark;

        if ($leave_id && $effectiveLeaveId) {

            $existing = DB::table('leave_credits')
                ->where('employee_no', $employee_no)
                ->where('leave_id', $effectiveLeaveId)
                ->where('as_of', $currentMonth)
                ->first();

            if ($existing) {

                $previousRemarks = trim($existing->remarks ?? '');

                $combinedRemarks = $previousRemarks !== ''
                    ? $previousRemarks . "\n" . $singleRemark
                    : $singleRemark;

                DB::table('leave_credits')
                    ->where('id', $existing->id)
                    ->update([
                        'deducted'   => (float) $existing->deducted + $toBeDeducted,
                        'balance'    => $new_balance,
                        'remarks'    => $combinedRemarks,
                        'updated_at' => now(),
                    ]);

            } else {

                DB::table('leave_credits')->insert([
                    'employee_no' => $employee_no,
                    'leave_id'    => $effectiveLeaveId,
                    'as_of'       => $currentMonth,
                    'previous'    => $remaining_balance,
                    'earned'      => 0,
                    'deducted'    => $toBeDeducted,
                    'balance'     => $new_balance,
                    'remarks'     => $singleRemark,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        return [
            'status'           => 'success',
            'message'          => 'Leave credits updated successfully.',
            'combined_remarks' => $combinedRemarks,
            'single_remarks'   => $singleRemark,
        ];
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('employee_no', function($row) {
                return $row->employee_no;
            })
            ->editColumn('name', function($row) {
                return $row->firstname . ' ' . $row->lastname;
            })
            ->editColumn('type', function($row) {
                return $row->name;  
            })
            ->editColumn('dates', function ($row) {
                return formatDateRanges($row->dates);
            })
            ->editColumn('status', function ($row) {
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
                return '
                    <div class="d-block d-md-flex gap-2 justify-content-start">
                        <a href="'.route('services.leaves.show', ['application' => $row->id]).'" 
                            class="btn btn-primary btn show-button ms-1 my-1" 
                            title="Show">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

}