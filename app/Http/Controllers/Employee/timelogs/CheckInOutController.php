<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CheckInOutRequest;
use App\Services\TimelogsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CheckInOutController extends Controller
{
    protected $timelogsServices;

    public function __construct(TimelogsServices $timelogsServices)
    {
        $this->timelogsServices = $timelogsServices;
    }

    public function index()
    {
        $user_id = auth()->user()->id;

        $query = $this->timelogsServices->getTimeLogs($user_id);

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.timelogs.checkin-out.index');
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

        DB::beginTransaction();

        try {
     
            $validatedData['user_id'] = auth()->user()->id;
            $validatedData['employee_no'] = auth()->user()->employee_no;

            $current_timelog = $this->timelogsServices->getTodaysLogs($validatedData['user_id']);
            
            if (    !empty($current_timelog['breakIn']) &&
                    !empty($current_timelog['breakIn']) &&
                    !empty($current_timelog['breakIn']) &&
                    !empty($current_timelog['breakIn'])
                ) {

                throw new \Exception('You have already completed all your logs for today. No further action is needed.');
            }
        

            // check if there is valid logs and create if no
            if($validatedData['type'] === 'timeOut') {
                $this->timelogsServices->straightToTimeOut($validatedData);
            }

            $timelog = DB::table('timelogs')->insert([
                'user_id'     => $validatedData['user_id'],
                'employee_no' => $validatedData['employee_no'] ?? null,
                'date_time'   => $validatedData['date_time'],
                'shift_id'   => 1,
                'work_schedule_id'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $time = \Carbon\Carbon::parse($validatedData['date_time'])->format('h:i:s A');

            DB::commit();

            return response()->json([
                'message' => 'Time log entry recorded successfully.',
                'data'    => $timelog,
                'time' => $time,
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
            ->rawColumns(['date', 'time_in', 'break_out', 'break_in', 'time_out'])
            ->make(true);
    }
}
