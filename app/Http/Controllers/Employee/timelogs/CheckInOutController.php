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
use Symfony\Component\HttpKernel\Exception\HttpException;
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

        $this->middleware('permission:emp.timelogs.view')->only(['index']);
        $this->middleware('permission:emp.timelogs.checkin-out')->only(['store']);
    }

    public function index()
    {
        $user_id = auth()->user()->id;

        $employee_no = $this->employeeService->getEmployeeNo($user_id);

        $is_allowed = $this->canUseWebTimeToday($employee_no)['allowed'];

        $query = $this->timelogsServices->getTimeLogs($user_id);

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.timelogs.checkin-out.index', compact(['employee_no', 'is_allowed']));
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

        $validatedData['user_id'] = auth()->user()->id;
        $validatedData['employee_no'] = auth()->user()->employee_no();

        // $isAllowedToUseWebAccess = $this->canUseWebTimeToday($validatedData['employee_no'])['allowed'];
        $isAllowedToUseWebAccess = $this->canUseWebTimeToday($validatedData['employee_no']);

        if (!$isAllowedToUseWebAccess['allowed']) {
            throw new HttpException(
                403,
                'You are not permitted to use Web Time today. Kindly record your time by scanning your fingerprint on the biometric device.'
            );
        }

        $user = User::find($validatedData['user_id']);
        $user_schedule = $user->getShiftAndWorkSchedule();

        // Get current timelogs
        $current_timelog = $this->timelogsServices->getTodaysLogs($validatedData['user_id']);

        if (!empty($current_timelog['timeOut']) && (FnEnum::BreakOut->value == $fn || FnEnum::BreakIn->value == $fn)) {
            throw new HttpException(
                403,
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
            throw new HttpException(
                403,
                'You have already completed all your logs for today. No further action is needed.'
            );
        }

        // Use current date and time (Philippine timezone)
        $now = Carbon::now('Asia/Manila');
        $validatedData['date_time'] = $now;

        // Handle straight time-out
        if ($validatedData['type'] === 'timeOut') {
            $this->timelogsServices->straightToTimeOut($validatedData);
        }

        DB::beginTransaction();

        try {

            // Insert time log
            $timelog = DB::table('timelogs')->insert([
                'user_id'           => $validatedData['user_id'],
                'employee_no'       => $validatedData['employee_no'],
                'date_time'         => $now,
                'fn'                => $fn,
                'shift_id'          => $user_schedule['shift_id'],
                'work_schedule_id'  => $user_schedule['work_schedule_id'],
                'created_at'        => now('Asia/Manila'),
                'updated_at'        => now('Asia/Manila'),
            ]);

            $time = $now->format('h:i:s A');

            DB::commit();

            return response()->json([
                'message' => 'Your time log was recorded successfully.',
                'reaason' => $isAllowedToUseWebAccess['reason'],
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

    public function canUseWebTimeToday(string $employeeNo): array
    {
        $now   = Carbon::now();
        $today = $now->toDateString(); // "2026-01-16"
        $dow   = $now->format('D');    // "Mon", "Tue", ...

        $rule = DB::table('web_time_access')
            ->where('employee_no', $employeeNo)
            ->where('effectivity_date', '<=', now())
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id') // tie-breaker
            ->first();


        if (!$rule) {
            return [
                'allowed' => false,
                'reason'  => 'No active Web Time access rule found.',
                'matched_rule_id' => null,
            ];
        }

        if ((int) $rule->always === 1) {
            // dd((int) $rule->always === 1);
            return [
                'allowed' => true,
                'reason'  => 'Allowed: always access.',
                'matched_rule_id' => $rule->id,
            ];
        }


        // Decode JSON safely
        $specificDates = $rule->specific_dates ? json_decode($rule->specific_dates, true) : [];
        $daysOfWeek    = $rule->days_of_week ? json_decode($rule->days_of_week, true) : [];

        $specificDates = is_array($specificDates) ? $specificDates : [];
        $daysOfWeek    = is_array($daysOfWeek) ? $daysOfWeek : [];

        // 2) SPECIFIC DATES
        if (in_array($today, $specificDates, true)) {
            return [
                'allowed' => true,
                'reason'  => "Allowed: today's date ($today) is in specific_dates.",
                'matched_rule_id' => $rule->id,
            ];
        }

        // dd($rule);


        // 3) DAYS OF WEEK
        if (in_array($dow, $daysOfWeek, true)) {
            return [
                'allowed' => true,
                'reason'  => "Allowed: today ($dow) is in days_of_week.",
                'matched_rule_id' => $rule->id,
            ];
        }


        return [
            'allowed' => false,
            'reason'  => 'Web Time is not allowed for you today based on your assigned schedule. Please use the biometric fingerprint scanner.',
            'matched_rule_id' => $rule->id,
        ];
    }
}
