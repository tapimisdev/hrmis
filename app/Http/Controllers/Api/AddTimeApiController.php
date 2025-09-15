<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTimeLogsRequest;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddTimeApiController extends Controller
{

    protected $timelog_service;

    public function __construct(TimelogsServices $timelog_service)
    {
        $this->timelog_service = $timelog_service;
    }

    public function store(StoreTimeLogsRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');

            // Mark existing logs as inactive
            $oldLogs = DB::table('timelogs')
                ->whereDate('date_time', $date)
                ->where('user_id', $validatedData['user_id'])
                ->get();

            if ($oldLogs->isNotEmpty()) {
                DB::table('timelogs')
                    ->whereDate('date_time', $date)
                    ->where('user_id', $validatedData['user_id'])
                    ->update(['is_active' => false]);
            }

            // Prepare each time entry as a separate record
            $timeEntries = [
                'time_in'  => $validatedData['time_in'] ?? null,
                'break_out'=> $validatedData['break_out'] ?? null,
                'break_in' => $validatedData['break_in'] ?? null,
                'time_out' => $validatedData['time_out'] ?? null,
            ];

            foreach ($timeEntries as $time) {
                DB::table('timelogs')->insert([
                    'user_id'          => $validatedData['user_id'],
                    'employee_no'      => $validatedData['employee_no'] ?? null,
                    'date_time'        => Carbon::parse($date . ' ' . $time)->format('Y-m-d H:i:s'),
                    'shift_id'         => $validatedData['shift'],
                    'work_schedule_id' => $validatedData['weeklyschedule'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
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

    public function edit(Request $request)
    {
         // Validate user_id and date
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'date'    => ['required', 'date'],
        ]);

        // Access validated data
        $userId = $validated['user_id'];
        $date = Carbon::parse($validated['date']);

        $logs = $this->timelog_service->getTimeLogsWithPeriod(
            $userId,
            $date->copy()->startOfDay(),  // start_date
            $date->copy()->endOfDay()     // end_date
        );

        return response(['data' => $logs, 'status' => 'success'], 200);
    }
}
