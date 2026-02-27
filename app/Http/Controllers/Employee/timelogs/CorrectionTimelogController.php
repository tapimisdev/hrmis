<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CorrectionRequest;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

use function PHPUnit\Framework\returnArgument;

class CorrectionTimelogController extends Controller
{

    protected $timelog_service;
    protected $employee_service;
    protected $EventService;

    protected $user_id;

    public function __construct(TimelogsServices $timelog_service, EmployeeService $employee_service, EventService $EventService)
    {
        $this->timelog_service = $timelog_service;
        $this->employee_service = $employee_service;
        $this->EventService = $EventService;

        $this->middleware('permission:emp.correction.view')->only('index');
        $this->middleware('permission:emp.correction.apply')->only(['store']);

    }

    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer',
        ]);

        $user_id = Auth::id();
        $employee_no = $this->employee_service->getEmployeeNo($user_id);

        $query = DB::table('timelog_corrections')
            ->whereMonth('date', $request->month)
            ->whereYear('date', $request->year)
            ->where('employee_no', $employee_no);

        return DataTables::of($query)

            ->editColumn('date', function ($row) {
                return $row->date
                    ? \Carbon\Carbon::parse($row->date)->format('m/d/Y')
                    : '-';
            })

            ->editColumn('time_in', function ($row) {
                return $row->time_in
                    ? \Carbon\Carbon::parse($row->time_in)->format('h:i A')
                    : '-';
            })

            ->editColumn('break_out', function ($row) {
                return $row->break_out
                    ? \Carbon\Carbon::parse($row->break_out)->format('h:i A')
                    : '-';
            })

            ->editColumn('break_in', function ($row) {
                return $row->break_in
                    ? \Carbon\Carbon::parse($row->break_in)->format('h:i A')
                    : '-';
            })

            ->editColumn('time_out', function ($row) {
                return $row->time_out
                    ? \Carbon\Carbon::parse($row->time_out)->format('h:i A')
                    : '-';
            })

            ->editColumn('overtime_in', function ($row) {
                return $row->overtime_in
                    ? \Carbon\Carbon::parse($row->overtime_in)->format('h:i A')
                    : '-';
            })

            ->editColumn('overtime_out', function ($row) {
                return $row->overtime_out
                    ? \Carbon\Carbon::parse($row->overtime_out)->format('h:i A')
                    : '-';
            })

            ->addColumn('attachment', function ($row) {
                if (!$row->attachment) {
                    return '-';
                }

                $url = Storage::url($row->attachment);

                return '<a href="'.$url.'" target="_blank" class="btn btn-sm btn-link">View</a>';
            })

            ->editColumn('status', function ($row) {
                if ($row->status === 'pending') {
                    return '<span class="badge bg-warning text-dark">Pending</span>';
                }
                if ($row->status === 'approved') {
                    return '<span class="badge bg-success">Approved</span>';
                }
                if ($row->status === 'rejected') {
                    return '<span class="badge bg-danger">Rejected</span>';
                }

                return $row->status;
            })

            ->rawColumns(['status', 'attachment'])

            ->make(true);
    }

    public function edit(Request $request) {
        
        // Validate user_id and date
        $validated = $request->validate([
            'date'    => ['required', 'date'],
        ]);

        // Access validated data
        $employee_no = auth()->user()->employee_no();

        $user_id = DB::table('employee_information')->where('employee_no', $employee_no)->value('user_id');

        $date = Carbon::parse($validated['date']);

        $logs = $this->timelog_service->getTimeLogsWithPeriod(
            $user_id,
            $date->copy()->startOfDay(),  // start_date
            $date->copy()->endOfDay()     // end_date
        );

        return response(['data' => $logs, 'status' => 'success'], 200);
    }

    public function store(CorrectionRequest $request) 
    {   

        $validatedData = $request->validated();
        $isDirectlyApproved = $validatedData['isDirectlyApproved'] ?? false;
        $employee_no = auth()->user()->employee_no(); 

        DB::beginTransaction();

        try {
            
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');

            // Helper function to combine date + time
            $combineDateTime = function($date, $time) {
                return $time ? Carbon::parse("$date $time")->format('Y-m-d H:i:s') : null;
            };

            // Handle attachment if exists
            if ($request->hasFile('attachment')) {

                $path = 'users/' . $employee_no . '/timelog-corrections-attachments/';
                $attachmentPath = $request->file('attachment')->store($path, 'public');

            }

            $schedule_and_Schift = DB::table('employee_shift_work_schedule')
                ->where('employee_no', $employee_no)
                ->latest('effectivity_date')
                ->first();

            $application_no = $this->generate_reference_no();

            $concern = (match ($validatedData['concern']) {
                'OO' => 'system_out_of_order',
                'F'=> 'failure_to_entry',
                'IE' => 'incorrect_entry'
            });

            $applicationId = DB::table('timelog_corrections')->insertGetID([
                'reference_no'      => $application_no,
                'employee_no'       => $employee_no,
                'date'              => $validatedData['date'],
                'time_in'           => $combineDateTime($date, $validatedData['time_in']),
                'break_out'         => $combineDateTime($date, $validatedData['break_out']),
                'break_in'          => $combineDateTime($date, $validatedData['break_in']),
                'time_out'          => $combineDateTime($date, $validatedData['time_out']),
                'overtime_in'       => $combineDateTime($date, $validatedData['overtime_in']),
                'overtime_out'      => $combineDateTime($date, $validatedData['overtime_out']),
                'shift_id'          => $schedule_and_Schift->shift_id ?? null,
                'work_schedule_id'  => $schedule_and_Schift->work_schedule_id,
                'attachment'        => $attachmentPath,
                'remarks'           => $validatedData['remarks'] ?? null,
                'concern'           => $concern,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if(!$isDirectlyApproved) {
                $sender = ucwords(Auth::user()->name);
                $payload = [
                    'type' => 'application',
                    'sender' => $sender,
                    'receiver' => 'admins',
                    'message' => '%b' . $sender . '%b filed a correction timelog (%bi' . strtoupper($application_no) . ') %bi',
                    'link' => '/admin/timekeeping/timelogs-correction?id=' . $applicationId
                ];
                $this->EventService->pushNotification($payload);

            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Correction Requested!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }    
    }

    private function generate_reference_no(): string
    {
        do {
            $date = now()->format('Ymd');
            $sequence = DB::table('timelog_corrections')
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $ref = 'TCR-' . $date . '-' . str_pad($sequence, 2, '0', STR_PAD_LEFT);
        } while (
            DB::table('timelog_corrections')
                ->where('reference_no', $ref)
                ->exists()
        );

        return $ref;
    }
}
