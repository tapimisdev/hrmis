<?php

namespace App\Http\Controllers\Api;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Services\ApplicationController;
use App\Http\Requests\Api\StoreTimeLogsRequest;
use App\Http\Requests\Employee\StoreAtroRequest;
use App\Services\TimelogsServices;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddTimeApiController extends Controller
{
    protected $timelog_service;
    protected $employee_service;

    protected $user_id;

    public function __construct(TimelogsServices $timelog_service, EmployeeService $employee_service, ApplicationController $applicationService)
    {
        $this->timelog_service = $timelog_service;
        $this->employee_service = $employee_service;
        $this->applicationService = $applicationService;
    }

    public function add_time(StoreTimeLogsRequest $request)
    {
        $validatedData = $request->validated();

        $employee_no = $validatedData['user_id']; // adjusted

        $user_id = $this->employee_service->getEmployeeUserId($employee_no);

        DB::beginTransaction();

        try {
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');

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
                ['fn' => FnEnum::TimeIn,      'time' => $validatedData['time_in'] ?? null],
                ['fn' => FnEnum::TimeOut,     'time' => $validatedData['time_out'] ?? null],
                ['fn' => FnEnum::BreakOut,    'time' => $validatedData['break_out'] ?? null],
                ['fn' => FnEnum::BreakIn,     'time' => $validatedData['break_in'] ?? null],
                ['fn' => FnEnum::OvertimeIn,  'time' => $validatedData['overtime_in'] ?? null],
                ['fn' => FnEnum::OvertimeOut, 'time' => $validatedData['overtime_out'] ?? null],
            ];

            foreach ($timeEntries as $entry) {
                if ($entry['time'] === null) {
                    continue;
                }

                DB::table('timelogs')->insert([
                    'user_id'          => $user_id,
                    'employee_no'      => $employee_no,
                    'date_time'        => Carbon::parse($date . ' ' . $entry['time'])->format('Y-m-d H:i:s'),
                    'shift_id'         => $validatedData['shift'],
                    'work_schedule_id' => $validatedData['weeklyschedule'],
                    'fn'               => $entry['fn']->value,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

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

    public function add_overtime(StoreAtroRequest $request)
    {
        $validatedData = $request->validated();

        $employee_no = $validatedData['user_id'];
        $user_id = $this->employee_service->getEmployeeUserId($employee_no);
        
        DB::beginTransaction();

        try {


            $atro = DB::table('overtime_applications')
                    ->insert([
                        'application_no' =>  generateApplicationNo('overtime_applications', 'PSL'),
                        'user_id' => $user_id,
                        'employee_no' => $employee_no,
                        'date' => $validatedData['date'],
                        'start_time' => $validatedData['start_time'],
                        'end_time' => $validatedData['end_time'],
                        'total_hours' => $validatedData['total_hours'],
                        'reason' => $validatedData['reason'],
                        'status' => 'approved',
                        'approver_id' => Auth::id(),
                        'approved_at' => now(),
                        'level' => null,
                        'levels' => null,
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ]);

            DB::commit();
            return response(['data' => $atro, 'status' => 'store success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage(), 'status' => 'store failed'], 500);
        }
    }

    public function getOvertime(Request $request)
    {
        $data = DB::table('overtimes')
                ->where('date', $request->input('date'))
                ->where('user_id', $request->input('user_id'))
                ->first();

        return response(['overtime' => $data, 'message' => 'show success'], 200);
    }

    public function edit(Request $request)
    {
         // Validate user_id and date
        $validated = $request->validate([
            'user_id' => ['required', 'exists:employee_information,employee_no'],
            'date'    => ['required', 'date'],
        ]);

        // Access validated data
        $employee_no = $validated['user_id'];

        $user_id = DB::table('employee_information')->where('employee_no', $employee_no)->value('user_id');

        $date = Carbon::parse($validated['date']);

        $logs = $this->timelog_service->getTimeLogsWithPeriod(
            $user_id,
            $date->copy()->startOfDay(),  // start_date
            $date->copy()->endOfDay()     // end_date
        );

        return response(['data' => $logs, 'status' => 'success'], 200);
    }
}
