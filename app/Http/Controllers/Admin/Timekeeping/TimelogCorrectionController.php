<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CorrectionRequest;
use App\Services\EmployeeService;
use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yajra\DataTables\DataTables;

class TimelogCorrectionController extends Controller
{

    protected $EventService;

    public function __construct(EventService $EventService)
    {
        $this->EventService = $EventService;
        $this->middleware('permission:hr.correction.view')->only('index');
        $this->middleware('permission:hr.correction.approval')->only(['edit', 'store']);
    }

    public function index()
    {
        if(request()->ajax()) {
            // Start query
            $query = DB::table('timelog_corrections as tc')
                ->select('tc.*', 'ep.firstname', 'ep.middlename', 'ep.lastname')
                ->leftJoin('employee_personal as ep', 'tc.employee_no', '=', 'ep.employee_no');

            // Filter by month
            $month = request()->get('month', date('n')); // default current month
            if($month) {
                $query->whereMonth('tc.date', $month);
            }

            // Filter by year
            $year = request()->get('year', date('Y')); // default current year
            if($year) {
                $query->whereYear('tc.date', $year);
            }

            // Filter by status
            $status = request()->get('status');
            if($status) {
                $query->where('tc.status', $status);
            }

            // Order by status ascending
            $query->orderBy('tc.created_at', 'desc');

            $data = $query->get();

            return $this->datatable($data);
        }

        return view('admin.pages.timekeeping.timelog-correction.index');
    }

    public function edit($id)
    {
        $correction = DB::table('timelog_corrections as tc')
                        ->leftJoin('employee_personal as ep', 'tc.employee_no', '=', 'ep.employee_no')
                        ->select('tc.*', 'ep.firstname', 'ep.middlename', 'ep.lastname')
                        ->where('tc.id', $id)
                        ->first();

        $correction->attachment = asset('storage/' . $correction->attachment);

        return response()->Json($correction);
    }

    public function approve($id)
    {
        
        $correction = DB::table('timelog_corrections')
            ->where('id', $id)
            ->first();

        if (!$correction) {
            throw new NotFoundHttpException('Timelog correction not found.');
        }

        if ($correction->status !== 'pending') {
            throw new NotFoundHttpException('This timelog correction has already been processed and cannot be processed again.');
        }

        $employee_no = $correction->employee_no;
        $employee_service = app(EmployeeService::class);
        $user_id = $employee_service->getEmployeeUserId($employee_no);

        DB::beginTransaction();

        try {

            $date = Carbon::parse($correction->date)->format('Y-m-d');

              // Mark existing logs as inactive
            $oldLogs = DB::table('timelogs')
                ->whereDate('date_time', $date)
                ->where('user_id', $user_id)   
                ->get();

            if ($oldLogs->isNotEmpty()) {
                DB::table('timelogs')
                    ->whereDate('date_time', $date)
                    ->where('user_id', $user_id)
                    ->update(['is_active' => false]);
            }


            $timeEntries = [
                ['fn' => FnEnum::TimeIn,      'time' => $correction->time_in ?? null],
                ['fn' => FnEnum::TimeOut,     'time' => $correction->time_out ?? null],
                ['fn' => FnEnum::BreakOut,    'time' => $correction->break_out ?? null],
                ['fn' => FnEnum::BreakIn,     'time' => $correction->break_in ?? null],
                ['fn' => FnEnum::OvertimeIn,  'time' => $correction->overtime_in ?? null],
                ['fn' => FnEnum::OvertimeOut, 'time' => $correction->overtime_out ?? null],
            ];


            foreach ($timeEntries as $entry) {
                if ($entry['time'] === null) {
                    continue;
                }

                DB::table('timelogs')->insert([
                    'user_id'          => $user_id,
                    'employee_no'      => $employee_no,
                    'date_time'        => Carbon::parse($entry['time'])->format('Y-m-d H:i:s'),
                    'shift_id'         => $correction->shift_id,
                    'work_schedule_id' => $correction->work_schedule_id,
                    'fn'               => $entry['fn']->value,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Update the status to approved
            DB::table('timelog_corrections')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'updated_at' => now()
            ]);


            $sender = ucwords(Auth::user()->name);
            $reciever = $user_id;
            $application_no = $correction->reference_no;
            $payload = [
                'type' => 'approved',
                'sender' => $sender,
                'receiver' => $reciever,
                'message' => '%b' . $sender . '%b has approved your timelog correction request (%bi' . strtoupper($application_no) . ') %bi',
                'link' => '/employee/check-in-out'
            ];
            $this->EventService->pushNotification($payload);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Time logs added/updated successfully',
                'old_logs' => $oldLogs
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }

    }

    public function reject($id)
    {
        
        $correction = DB::table('timelog_corrections')
            ->where('id', $id)
            ->first();

        if (!$correction) {
            throw new NotFoundHttpException('Timelog correction not found.');
        }

        if ($correction->status !== 'pending') {
            throw new NotFoundHttpException('This timelog correction has already been processed and cannot be processed again.');
        }

         // Update the status to approved
        DB::table('timelog_corrections')
            ->where('id', $id)
            ->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);

        $employee_no = $correction->employee_no;
        $employee_service = app(EmployeeService::class);
        $user_id = $employee_service->getEmployeeUserId($employee_no);

        $sender = ucwords(Auth::user()->name);
        $reciever = $user_id;
        $application_no = $correction->reference_no;
        $payload = [
            'type' => 'rejected',
            'sender' => $sender,
            'receiver' => $reciever,
            'message' => '%b' . $sender . '%b has rejected your timelog correction request (%bi' . strtoupper($application_no) . ') %bi',
            'link' => '/employee/check-in-out'
        ];
        $this->EventService->pushNotification($payload);

        return response()->json([
            'message' => 'Timelog correction rejected.'
        ]);
    }

    private function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function ($row) {
                return $row->firstname . ' ' . $row->middlename . ' ' . $row->lastname;
            })
            ->editColumn('date', function ($row) {
                return Carbon::parse($row->date)->format('M d, Y');
            })
            ->editColumn('status', function ($row) {
                switch ($row->status) {
                    case 'pending':
                        return '<span class="badge bg-secondary">Pending</span>';
                    case 'approved':
                        return '<span class="badge bg-success">Approved</span>';
                    case 'rejected':
                        return '<span class="badge bg-danger">Rejected</span>';
                    default:
                        return '<span class="badge bg-primary">' . $row->status . '</span>';
                }
            })
            ->editColumn('applied_at', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                return '
                <div class="d-block d-md-flex gap-2 justify-content-start">
                    <button data-id="' . $row->id . '" 
                        class="btn btn-primary btn ms-1 my-1 show-button" 
                        title="View">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                ';
            })
            ->rawColumns(['actions', 'name', 'status', 'date', 'applied_at'])
            ->make(true);
    }
}
