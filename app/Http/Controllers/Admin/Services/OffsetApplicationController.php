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

class OffsetApplicationController extends Controller {

    protected $employeeService;
    protected $generateService;
    protected $EventService;

    public function __construct(EmployeeService $employeeService, EventService $EventService)
    {
        $this->middleware('permission:hr.offset_approval.view')->only(['view', 'show']);
        $this->middleware('permission:hr.offset_approval.save')->only('save');
        $this->employeeService = $employeeService;
        $this->EventService = $EventService;
    }

    public function getRawData(?int $id = null)
    {
        $applications = DB::table('offset_applications as la')
            ->leftJoin('employee_personal as p', 'la.employee_no', '=', 'p.employee_no')
            ->leftJoin('offset_dates as ld', 'ld.offset_application_id', '=', 'la.id')
            ->where('ld.isActive', true)
            ->select(
                'p.firstname',
                'p.lastname',
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.credit_equivalent',
                'la.reason',
                'la.status',
                'la.created_at',
                'la.updated_at',
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
                'la.credit_equivalent',
                'la.reason',
                'la.status',
                'la.created_at',
                'la.updated_at',
                'p.firstname',
                'p.lastname'
            )
            ->orderByDesc('la.created_at')
            ->get();

        // Attachments
        $attachments = DB::table('offset_attachments')
            ->select('offset_application_id', 'file_name', 'file_path', 'file_type')
            ->whereIn('offset_application_id', $applications->pluck('id'))
            ->get()
            ->groupBy('offset_application_id');

        // Approvals
        $approvalsRaw = DB::table('offset_approvals')
            ->join('employee_information', 'offset_approvals.user_id', '=', 'employee_information.user_id')
            ->join('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->select(
                'offset_approvals.status',
                'offset_approvals.offset_application_id',
                'offset_approvals.user_id',
                'offset_approvals.level',
                'employee_information.employee_no',
                'employee_personal.firstname',
                'employee_personal.lastname'
            )
            ->whereIn('offset_approvals.offset_application_id', $applications->pluck('id'))
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

        return view('admin.pages.services.offset.index');
    }
    
    public function show(int $id) 
    {

        $data = $this->getRawData($id)[0] ?? [];
        
        if(!$data) {
            return redirect()->back();
        }

        $employee_no = $data->employee_no;    
        $currentMonth = Carbon::now()->format('Y-m');
        $latestCredits = $this->employeeService->getOffsetCreditsByMonthYear($employee_no, $currentMonth);
        $remaining_balance = (float) $latestCredits['current']?->balance ?? 0;
        $toBeDeducted = (float) $this->computeEquivalent($data->dates);
        $new_balance = $remaining_balance - $toBeDeducted;

        $hasBalance = false;

        if($toBeDeducted > $remaining_balance) {
            $hasBalance = false;
        } else {
            $hasBalance = true;
        }

        $computation = [
            'remaining_balance' => number_format($remaining_balance, 2),
            'deduction' => number_format($toBeDeducted, 2),
            'new_balance' => number_format($new_balance, 2)
        ];

        return view('admin.pages.services.offset.show', compact('employee_no', 'id', 'data', 'hasBalance', 'computation'));
      
    }

    public function computeEquivalent($dates): float
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
            'id' => 'required|exists:offset_applications,id',
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

            $existingData = DB::table('offset_applications')
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

            DB::table('offset_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'credit_remarks' => $updateCredits['single_remarks'] ?? null,
                    'actioned_by' => Auth::id() ?? null
                ]);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your offset application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/offset?show=true&id=' . $existingData->id
            ];
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Offset application has been approved!',
                'redirect' => route('services.offset.show', ['application' => $id])
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

            $existingData = DB::table('offset_applications')
                ->where('id', $id)
                ->first();

            if (!$existingData) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            DB::table('offset_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'actioned_by' => Auth::id() ?? null,
                    'remarks' => $payload['remarks'] ?? null
                ]);

            DB::table('offset_approvals')
                ->where('offset_application_id', $id)
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
                'message' => '%b' . $sender . '%b has rejected your offset application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/offset?show=true&id=' . $existingData->id
            ];
            $this->EventService->pushNotification($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Offset application has been rejected!',
                'redirect' => route('services.offset.show', ['application' => $id])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCredits(string $id)
    {
        $data = $this->getRawData($id)->first();

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to find offset application ID: ' . $id,
            ], 404);
        }

        $employeeNo  = $data->employee_no;
        $currentMonth = now()->format('Y-m');

        // --- Fetch latest offset credits for current month ---
        $latestCredits = $this->employeeService
            ->getOffsetCreditsByMonthYear($employeeNo, $currentMonth);

        $remainingBalance = (float) ($latestCredits['current']->balance ?? 0);

        $datesByMonth = [];
        $toBeDeducted = 0;

        // --- Process dates ---
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

            $toBeDeducted += $credit;

            $datesByMonth[$month][] = [
                'day'    => $day,
                'shift'  => $shift,
                'credit' => $credit,
            ];
        }

        $newBalance = $remainingBalance - $toBeDeducted;

        // --- Build SINGLE remark (new only) ---
        $singleRemark = collect($datesByMonth)
            ->map(function ($days, $month) {

                $totalCredit = 0;

                $dayStrings = collect($days)->map(function ($d) use (&$totalCredit, $month) {
                    $totalCredit += $d['credit'];

                    $shiftShort = match ($d['shift']) {
                        'morning'   => 'AM',
                        'afternoon' => 'PM',
                        'wholeday'  => 'WD',
                        default     => strtoupper($d['shift']),
                    };

                    return "{$month} {$d['day']} ({$shiftShort})";
                })->implode(', ');

                return sprintf("%s (Eqv: %.2f)", $dayStrings, $totalCredit);

            })
            ->implode(' | ');

            $combinedRemarks = $singleRemark;

        // --- Insert / Update current month offset_credits ---
        $existing = DB::table('offset_credits')
            ->where('employee_no', $employeeNo)
            ->where('as_of', $currentMonth)
            ->first();

        if ($existing) {
            $previousRemarks = trim($existing->remarks ?? '');

            $combinedRemarks = $previousRemarks
                ? $previousRemarks . "\n" . $singleRemark
                : $singleRemark;

            DB::table('offset_credits')
                ->where('id', $existing->id)
                ->update([
                    'deducted'   => (float) $existing->deducted + $toBeDeducted,
                    'balance'    => $newBalance,
                    'remarks'    => $combinedRemarks,
                    'updated_at' => now(),
                ]);

        } else {

            DB::table('offset_credits')->insert([
                'employee_no' => $employeeNo,
                'as_of'       => $currentMonth,
                'previous'    => $remainingBalance,
                'earned'      => 0,
                'deducted'    => $toBeDeducted,
                'balance'     => $newBalance,
                'remarks'     => $singleRemark,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // --- Recalculate future balances ---
        $runningBalance = $newBalance;

        $futureCredits = DB::table('offset_credits')
            ->where('employee_no', $employeeNo)
            ->where('as_of', '>', $currentMonth)
            ->orderBy('as_of')
            ->get();

        foreach ($futureCredits as $credit) {
            $updatedBalance = $runningBalance + (float) $credit->earned - (float) $credit->deducted;

            DB::table('offset_credits')
                ->where('id', $credit->id)
                ->update([
                    'previous'   => $runningBalance,
                    'balance'    => $updatedBalance,
                    'updated_at' => now(),
                ]);

            $runningBalance = $updatedBalance;
        }

        return [
            'status'           => 'success',
            'message'          => 'Offset credits updated successfully.',
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
                        <a href="'.route('services.offset.show', ['application' => $row->id]).'" 
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