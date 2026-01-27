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
            ->select(
                'p.firstname',
                'p.lastname',
                'la.id',
                'la.name',
                'la.user_id',
                'la.employee_no',
                'la.days',
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
                'la.days',
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

            DB::table('offset_applications')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id() ?? null
                ]);

            $this->updateCredits($id);

            $sender = ucwords(Auth::user()->name);
            $reciever = $existingData->user_id;
            $application_no = $existingData->application_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your offset application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/offset'
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
                'link' => '/employee/offset'
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
        DB::beginTransaction();

        try {
            // --- Step 0: Fetch offset application data ---
            $data = $this->getRawData($id)->first();

            if (!$data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to find offset application ID: ' . $id,
                ], 404);
            }

            $employee_no  = $data->employee_no;
            $currentMonth = Carbon::now()->format('Y-m');

            // --- Step 1: Fetch latest offset credits for the current month ---
            $latestCredits = $this->employeeService
                ->getOffsetCreditsByMonthYear($employee_no, $currentMonth);

            $remaining_balance = (float) ($latestCredits['current']->balance ?? 0);
            $toBeDeducted     = (float) $this->computeEquivalent($data->dates);
            $new_balance      = $remaining_balance - $toBeDeducted;

            // --- Step 2: Group dates by month ---
            $datesByMonth = [];
            foreach ($data->dates as $d) {
                $dateObj = Carbon::parse($d['date']);
                $month   = strtoupper($dateObj->format('M')); // e.g., JAN
                $day     = $dateObj->format('j');            // day number

                if (!isset($datesByMonth[$month])) $datesByMonth[$month] = [];
                $datesByMonth[$month][] = $day;
            }

            // --- Step 3: Build formatted remark ---
            // Example: "JAN 1,2,3 (3 days) | FEB 4,5 (2 days)"
            $formattedRemark = collect($datesByMonth)
                ->map(function ($days, $month) {
                    $totalDays = count($days);
                    return sprintf("%s %s - (%s %s)", $month, implode(', ', $days), $totalDays, $totalDays === 1 ? 'day' : 'days');
                })
                ->implode(" | ");

            // --- Step 4: Update or insert offset_credits for current month ---
            $existing = DB::table('offset_credits')
                ->where('employee_no', $employee_no)
                ->where('as_of', $currentMonth)
                ->first();

            if ($existing) {
                $remarks = trim($existing->remarks ?? '');
                if ($remarks) $remarks .= "\n"; // append new line
                $remarks .= $formattedRemark;

                DB::table('offset_credits')
                    ->where('id', $existing->id)
                    ->update([
                        'deducted'   => (float) $existing->deducted + $toBeDeducted,
                        'balance'    => $new_balance,
                        'remarks'    => $remarks,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('offset_credits')->insert([
                    'employee_no' => $employee_no,
                    'as_of'       => $currentMonth,
                    'previous'    => $remaining_balance,
                    'earned'      => 0,
                    'deducted'    => $toBeDeducted,
                    'balance'     => $new_balance,
                    'remarks'     => $formattedRemark,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $runningBalance = $new_balance;

            // --- Step 5: Recalculate future offset_credits for this offset type ---
            $futureCredits = DB::table('offset_credits')
                ->where('employee_no', $employee_no)
                ->where('as_of', '>', $currentMonth)
                ->orderBy('as_of')
                ->get();

            foreach ($futureCredits as $credit) {
                $newBalance = $runningBalance + (float) $credit->earned - (float) $credit->deducted;

                DB::table('offset_credits')
                    ->where('id', $credit->id)
                    ->update([
                        'previous'   => $runningBalance,
                        'balance'    => $newBalance,
                        'updated_at' => now(),
                    ]);

                $runningBalance = $newBalance;
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Offset credits updated and future balances adjusted.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error updating offset credits.',
                'error'   => $e->getMessage(),
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