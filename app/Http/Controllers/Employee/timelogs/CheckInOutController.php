<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CheckInOutRequest;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use function PHPUnit\Framework\isEmpty;

class CheckInOutController extends Controller
{
    protected $timelogsServices;
    protected $employeeService;

    public function __construct(TimelogsServices $timelogsServices, EmployeeService $employeeService)
    {
        $this->timelogsServices = $timelogsServices;
        $this->employeeService = $employeeService;
    }

    public function index()
    {
        $user_id = auth()->user()->id;

        $employee_no = $this->employeeService->getEmployeeNo($user_id);

        $query = $this->timelogsServices->getTimeLogs($user_id);

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.timelogs.checkin-out.index', compact('employee_no'));
    }

    public function create()
    {
        return view('employee.pages.timelogs.checkin-out.create');
    }

    public function todayLogs()
    {
        $logs = $this->timelogsServices->getTodaysLogs();

        return response()->json(['data' => $logs]);
    }

    public function store(CheckInOutRequest $request)
    {
        $validatedData = $request->validated();
        $fn = $validatedData['type'] ?? null;

        DB::beginTransaction();

        try {
            $validatedData['user_id'] = auth()->user()->id;
            $validatedData['employee_no'] = auth()->user()->employee_no;

            $user = User::find($validatedData['user_id']);
            $user_schedule = $user->getShiftAndWorkSchedule();

            // Get current timelogs
            $current_timelog = $this->timelogsServices->getTodaysLogs($validatedData['user_id']);

            if (!empty($current_timelog['timeOut']) && (FnEnum::BreakOut->value == $fn || FnEnum::BreakIn->value == $fn)) {
                throw new \Exception(
                    'You have already timed out for today. If you need to log a break in or break out, please request a timelog adjustment from your supervisor.'
                );
            }

            // Prevent duplicate logging for today
            if (
                !empty($current_timelog['timeIn']) &&
                !empty($current_timelog['breakOut']) &&
                !empty($current_timelog['breakIn']) &&
                !empty($current_timelog['timeOut']) &&
                !empty($current_timelog['overtimeIn']) &&
                !empty($current_timelog['overtimeOut'])
            ) {
                throw new \Exception('You have already completed all your logs for today. No further action is needed.');
            }

            // Use current date and time (Philippine timezone)
            $now = Carbon::now('Asia/Manila');
            $validatedData['date_time'] = $now;

            // Handle straight time-out
            if ($validatedData['type'] === 'timeOut') {
                $this->timelogsServices->straightToTimeOut($validatedData);
            }

            // Insert time log
            $timelog = DB::table('timelogs')->insert([
                'user_id'           => $validatedData['user_id'],
                'employee_no'       => $validatedData['employee_no'],
                'date_time'         => $now, // Use $now instead of request input
                'fn'                => $fn,
                'shift_id'          => $user_schedule['shift_id'],
                'work_schedule_id'  => $user_schedule['work_schedule_id'],
                'created_at'        => now('Asia/Manila'),
                'updated_at'        => now('Asia/Manila'),
            ]);

            $time = $now->format('h:i:s A');

            DB::commit();

            return response()->json([
                'message' => 'Time log entry recorded successfully.',
                'data'    => $timelog,
                'time'    => $time,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed to record time log entry.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date', function ($row) {
                return \Carbon\Carbon::parse($row['date'])->format('F d, Y (l)') ?? '-- : -----';
            })
            ->addColumn('time_in', function ($row) {
                
                if($row['time_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['time_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('break_out', function ($row) {

                if($row['break_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['break_out'])->format('h:i:s A');
            })
            ->addColumn('break_in', function ($row) {
                
                if($row['break_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['break_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('time_out', function ($row) {
                
                if($row['time_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['time_out'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('overtime_in', function ($row) {
                
                if($row['overtime_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['overtime_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('overtime_out', function ($row) {
                
                if($row['overtime_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['overtime_out'])->format('h:i:s A') ?? '-- : -----';
            })
            ->rawColumns(['date', 'time_in', 'break_out', 'break_in', 'time_out', 'overtime_in', 'overtime_out'])
            ->make(true);
    }
}
