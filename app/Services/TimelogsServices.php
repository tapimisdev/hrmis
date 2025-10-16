<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\throwException;

class TimelogsServices {

    /**
     * Retrieve all time logs for a given user.
     *
     * This method fetches all active time logs for the specified user and 
     * formats them into a structured output.
     *
     * @param  int  $userId  The ID of the user.
     * @return mixed         The formatted collection of time logs.
     */
    public function getTimeLogs($userId)
    {
        // Get all logs for the user
        $timelogs = $this->fetchLogs($userId);
        return $this->formatLogs($timelogs);
    }

    /**
     * Retrieve time logs for a given user within a specific date range.
     *
     * This method fetches all active time logs for the specified user that 
     * fall within the provided date range, and formats them into a 
     * structured output.
     *
     * @param  int     $userId    The ID of the user.
     * @param  string  $startDate The start date (YYYY-MM-DD or full datetime).
     * @param  string  $endDate   The end date (YYYY-MM-DD or full datetime).
     * @return mixed              The formatted collection of time logs.
     */
    public function getTimeLogsWithPeriod($userId, $startDate, $endDate)
    {
        // Get logs within a date range
        $timelogs = $this->fetchLogs($userId, $startDate, $endDate);
        return $this->formatLogs($timelogs);
    }

    /**
     * Fetch raw time logs from the database.
     *
     * This private method queries the `timelogs` table for active logs 
     * associated with a given user. Optionally, it filters results within 
     * a provided date range. The results are grouped by date for easier 
     * processing.
     *
     * @param  int         $userId    The ID of the user.
     * @param  string|null $startDate Optional start date for filtering (YYYY-MM-DD or full datetime).
     * @param  string|null $endDate   Optional end date for filtering (YYYY-MM-DD or full datetime).
     * @return \Illuminate\Support\Collection A collection of logs grouped by date.
     */

    private function fetchLogs($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('timelogs')
            ->where('is_active', true)
            ->where('user_id', $userId)
            ->orderBy('date_time', 'asc');

        // Apply date range filter if both dates are provided
        if ($startDate && $endDate) {
            $query->whereBetween('date_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        // Group logs by date (e.g., "2025-09-18")
        $grouped = $query->get()->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->date_time)->toDateString();
        });

        // Ensure only the first "time in" per day is kept
        $grouped->map(function ($logs) {
            $seen = [];
            return $logs->filter(function ($log) use (&$seen) {
                // Cast type to int for comparison
                $type = (int) $log->fn;

                // If it's a time in and we already saw one, skip it
                if ($type === \App\Enums\FnEnum::TimeIn->value) {
                    if (in_array($type, $seen, true)) {
                        return false;
                    }
                }

                $seen[] = $type;
                return true;
            });
        });

        return $grouped;
    }

    /**
     * Format grouped timelogs into structured array
     * @param \Illuminate\Support\Collection $timelogs
     * @return \Illuminate\Support\Collection
     */
    private function formatLogs($timelogs)
    {
        $result = [];

        foreach ($timelogs as $date => $logs) {
            $valid = $this->getValidLogs($logs);

            $result[] = [
                'date'      => $date,
                'time_in'   => $valid['in']->date_time ?? null,
                'break_out' => $valid['break_out']->date_time ?? null,
                'break_in'  => $valid['break_in']->date_time ?? null,
                'time_out'  => $valid['out']->date_time ?? null,
                'overtime_in'  => $valid['overtime_in']->date_time ?? null,
                'overtime_out'  => $valid['overtime_out']->date_time ?? null,
                'shift_id'  => $valid['out']->shift_id ?? null,
                'work_schedule_id'  => $valid['out']->work_schedule_id ?? null,
            ];
        }

        return collect($result)->sortByDesc('date')->values();
    }

   /**
     * Process raw logs and return a structured set of valid logs.
     *
     * This method:
     *  - Sorts the logs by date and time
     *  - Removes near-duplicate entries within a threshold (default: 10 seconds)
     *  - Extracts the main DTR logs (time-in, break-out, break-in, time-out)
     *  - Separates and assigns overtime logs if present
     *
     * @param  \Illuminate\Support\Collection|array  $logs
     *         The raw logs (each log should contain at least `date_time`, 
     *         `shift_id`, and `work_schedule_id`).
     *
     * @return array  Structured logs with the following keys:
     *                - in
     *                - break_out
     *                - break_in
     *                - out
     *                - overtime_in
     *                - overtime_out
     *                - shift_id
     *                - work_schedule_id
     */
    public function getValidLogs($logs)
    {
        // Normalize input into a collection & sort chronologically
        $logs = collect($logs)->sortBy('date_time')->values();

        // Default structured result
        $validLogs = [
            'in'              => null,
            'break_out'       => null,
            'break_in'        => null,
            'out'             => null,
            'overtime_in'     => null,
            'overtime_out'    => null,
            'shift_id'        => $logs->first()->shift_id ?? null,
            'work_schedule_id'=> $logs->first()->work_schedule_id ?? null,
        ];

        if ($logs->isEmpty()) {
            return $validLogs;
        }

        // Assign logs by type (only first per type)
        foreach ($logs as $log) {
            $type = (int) $log->fn;

            switch ($type) {
                case \App\Enums\FnEnum::TimeIn->value:
                    if (!$validLogs['in']) {
                        $validLogs['in'] = $log;
                    }
                    break;

                case \App\Enums\FnEnum::TimeOut->value:
                    if (!$validLogs['out']) {
                        $validLogs['out'] = $log;
                    }
                    break;

                case \App\Enums\FnEnum::BreakOut->value:
                    if (!$validLogs['break_out']) {
                        $validLogs['break_out'] = $log;
                    }
                    break;

                case \App\Enums\FnEnum::BreakIn->value:
                    if (!$validLogs['break_in']) {
                        $validLogs['break_in'] = $log;
                    }
                    break;

                case \App\Enums\FnEnum::OvertimeIn->value:
                    if (!$validLogs['overtime_in']) {
                        $validLogs['overtime_in'] = $log;
                    }
                    break;

                case \App\Enums\FnEnum::OvertimeOut->value:
                    if (!$validLogs['overtime_out']) {
                        $validLogs['overtime_out'] = $log;
                    }
                    break;
            }
        }

        return $validLogs;
    }


    public function parseTime($time) {
        return !empty($time) ? Carbon::parse($time)->format('h:i A') : null;
    }

    /**
     * Get today's valid timelogs for a user.
     *
     * This method:
     *  - Defaults to the authenticated user if no `$user_id` is provided
     *  - Fetches today's logs from the database (`timelogs` table)
     *  - Filters them into valid logs (time in, break out, break in, time out, overtime)
     *  - Formats each timestamp into human-readable 12-hour time with AM/PM
     *
     * @param  int|null  $user_id  The ID of the user. Defaults to the logged-in user.
     *
     * @return array  Formatted logs for today with keys:
     *                - date (string: YYYY-MM-DD)
     *                - timeIn (string|null: e.g., "08:00:00 AM")
     *                - breakOut (string|null)
     *                - breakIn (string|null)
     *                - timeOut (string|null)
     *                - overtimeIn (string|null)
     *                - overtimeOut (string|null)
     */
    public function getTodaysLogs($user_id = null)
    {
        // Use provided user ID or fallback to currently authenticated user
        $user_id = $user_id ?? auth()->user()->id;

        // Get today's date (YYYY-MM-DD)
        $today = \Carbon\Carbon::now()->toDateString();

        // Fetch raw logs for today
        $logs = DB::table('timelogs')
            ->where('is_active', true)
            ->where('user_id', $user_id)
            ->whereDate('date_time', $today)
            ->orderBy('date_time', 'asc')
            ->get();

        // Process into valid log structure
        $valid = $this->getValidLogs($logs);

        // Format output into 12-hour time with AM/PM
        return [
            'date'        => $today,
            'timeIn'      => isset($valid['in']->date_time) 
                ? \Carbon\Carbon::parse($valid['in']->date_time)->format('h:i:s A') 
                : null,
            'breakOut'    => isset($valid['break_out']->date_time) 
                ? \Carbon\Carbon::parse($valid['break_out']->date_time)->format('h:i:s A') 
                : null,
            'breakIn'     => isset($valid['break_in']->date_time) 
                ? \Carbon\Carbon::parse($valid['break_in']->date_time)->format('h:i:s A') 
                : null,
            'timeOut'     => isset($valid['out']->date_time) 
                ? \Carbon\Carbon::parse($valid['out']->date_time)->format('h:i:s A') 
                : null,
            'overtimeIn'  => isset($valid['overtime_in']->date_time) 
                ? \Carbon\Carbon::parse($valid['overtime_in']->date_time)->format('h:i:s A') 
                : null,
            'overtimeOut' => isset($valid['overtime_out']->date_time) 
                ? \Carbon\Carbon::parse($valid['overtime_out']->date_time)->format('h:i:s A') 
                : null,
        ];
    }

    /**
     * Get the most recent (last) active timelog entry for the authenticated user.
     *
     * This method:
     *  - Uses the currently authenticated user's ID
     *  - Queries the `timelogs` table for active logs
     *  - Orders logs by `date_time` in descending order
     *  - Returns the most recent log entry
     *
     * @return object|null  The latest timelog record as a stdClass object,
     *                      or null if no logs are found.
     */
    public function getLastLog()
    {
        $user_id = auth()->user()->id;

        return DB::table('timelogs')
            ->where('is_active', true)
            ->where('user_id', $user_id)
            ->orderBy('date_time', 'desc')
            ->first();
    }

    /**
     * Force a user straight to "time out" while auto-inserting missing break logs if necessary.
     *
     * This method:
     *  - Retrieves today's logs for the given user
     *  - Ensures the user has already clocked in
     *  - Prevents duplicate time-out if all logs are already filled (timeIn, breakOut, breakIn, timeOut)
     *  - If missing, automatically inserts synthetic `breakOut` and `breakIn` logs
     *    (adjusted a few seconds before the provided `date_time`) to preserve log sequence
     *
     * @param  array  $payload  The data payload containing:
     *                          - user_id (int)       : The user's ID
     *                          - employee_no (string): Optional employee number
     *                          - date_time (string)  : The intended time-out datetime (Y-m-d H:i:s)
     *
     * @throws \Exception If no clock-in exists or the user is already clocked out.
     *
     * @return void
     */
    public function straightToTimeOut($payload)
    {
        // Fetch today's logs for this user
        $current_timelog = $this->getTodaysLogs($payload['user_id']);

        // Must have a valid time-in
        if (empty($current_timelog['timeIn'])) {
            throw new \Exception('No clock in yet.');
        }

        // Prevent time-out if logs already complete
        if (
            !empty($current_timelog['timeIn']) &&
            !empty($current_timelog['breakOut']) &&
            !empty($current_timelog['breakIn']) &&
            !empty($current_timelog['timeOut'])
        ) {
            throw new \Exception('You are already time out');
        }

        // Prepare placeholders
        $breakOut = $current_timelog['breakOut'];
        $breakIn  = $current_timelog['breakIn'];

        // If no break-out, insert an artificial one 20 seconds before time-out
        if (empty($current_timelog['breakOut'])) {
            $breakOut = Carbon::parse($payload['date_time'])->subSecond(20);

            DB::table('timelogs')->insert([
                'user_id'           => $payload['user_id'],
                'employee_no'       => $payload['employee_no'] ?? null,
                'date_time'         => $breakOut,
                'shift_id'          => 1, // Hardcoded (consider making dynamic)
                'work_schedule_id'  => 1, // Hardcoded (consider making dynamic)
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // If no break-in, insert an artificial one 10 seconds before time-out
        if (empty($current_timelog['breakIn'])) {
            $breakIn = Carbon::parse($payload['date_time'])->subSecond(10);

            DB::table('timelogs')->insert([
                'user_id'           => $payload['user_id'],
                'employee_no'       => $payload['employee_no'] ?? null,
                'date_time'         => $breakIn,
                'shift_id'          => 1, // Hardcoded (consider making dynamic)
                'work_schedule_id'  => 1, // Hardcoded (consider making dynamic)
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /**
     * Generate a default "no data" attendance record for a user.
     *
     * This function creates a standardized array representing an empty or missing
     * attendance entry (e.g., absent, holiday, or no logs). All time-related
     * fields are set to `null`, while numeric fields are initialized to `0`.
     *
     * Example use case:
     * - Employee has no time logs for the day.
     * - System needs a placeholder record to avoid null references in payroll
     *   or attendance reports.
     *
     * @param string $remarks Remarks or reason for the no-data entry (e.g., "absent", "holiday").
     * @param int    $userId  The user's ID.
     *
     * @return array {
     *     @type int         $user_id          The employee's ID
     *     @type ?string     $time_in          Employee's actual time-in (null if absent)
     *     @type ?string     $time_out         Employee's actual time-out (null if absent)
     *     @type ?string     $break            Break duration or record (null if absent)
     *     @type ?string     $overtime         Overtime logs (null if absent)
     *     @type ?int        $shift_id         Assigned shift ID (null if no shift recorded)
     *     @type ?int        $work_schedule_id Work schedule ID (null if no schedule recorded)
     *     @type int         $ot_hrs           Total overtime hours (0 if none)
     *     @type int         $total_paid_hrs   Total payable hours (0 if absent)
     *     @type int         $doble            Double pay hours (0 if none)
     *     @type int         $late_undertime   Total late/undertime minutes (0 if none)
     *     @type string      $remarks          Remarks/reason (e.g., "absent", "holiday")
    * }
    */
    public function insertNoData($remarks, $userId, $today, $double = 0)
    {
        return [
            'date'              => $today,
            'user_id'           => $userId,
            'time_in'           => null,
            'time_out'          => null,
            'break'             => null,
            'overtime'          => null,
            'shift_id'          => null,
            'work_schedule_id'  => null,
            'ot_mins'           => 0,
            'total_time_work'    => 0,
            'doble'             => $double,
            'late_undertime'    => 0,
            'paid_hours'        => 0,
            'remarks'           => $remarks,
        ];
    }

    /**  ==========================================================
     * =                  Functions for DTR                    =
     *   ==========================================================
     */
    
    /**
     * Check if a user has a leave application on a given date.
     *
     * This function verifies whether the employee is on leave for the specified date,
     * based on the leave_applications table. It checks if the date falls within
     * the leave's start_date and end_date, and if the status is either
     * "approved" or "pending".
     *
     * Rules:
     * - If no leave record is found, the employee is not on leave.
     * - If a leave is found with status "pending", return status as "pending leave".
     * - If a leave is found with status "approved", return status as "leave".
     *
     * @param string|\Carbon\Carbon $date   The date to check (Y-m-d or Carbon instance)
     * @param int                   $userId The user's ID
     *
     * @return array {
     *     @type bool   $is_leave True if the employee has a leave record on the given date
     *     @type string $status   "leave" if approved, "pending leave" if still pending
     * }
     */
    public function checkIfLeave($date, $userId)
    {
        $isLeave = false;
        $status = 'pending leave';

        $leave = DB::table('leave_applications')
            ->where('user_id', $userId)
            ->where(function($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->where(function($query) {
                $query->where('status', 'approved')
                    ->orWhere('status', 'pending');
            })
            ->first();  

        if ($leave) {
            $isLeave = true;

            if ($leave->status === 'approved') {
                $status = 'leave';
            }
        }

        return [
            'is_leave' => $isLeave,
            'status'   => $status
        ];
    }


    /**
     * Check and compute overtime for a given user and date.
     *
     * @param  string  $date   The target date (Y-m-d).
     * @param  int     $userId The user's ID.
     * @param  array   $computedTimeLogOvertime Precomputed timelog data (expects 'combined_hours').
     * 
     * @return array{
     *     is_overtime: bool,
     *     overtime_hrs: float,
     *     status: string
     * }
     * 
     * Logic:
     * - Finds an overtime request (approved or pending) for the user on the given date.
     * - If approved → actual overtime = min(timelog hours, approved hours).
     * - If pending → status remains "pending overtime", overtime_hrs = 0.
     * - If no request → returns default (false, 0, "pending overtime").
     */
    public function checkOvertime($date, $userId, $computedTimeLogOvertime)
    {
        // Hours logged from timelog computation
        $timelogHours = (double) $computedTimeLogOvertime['decimal'];

        // Find overtime record (only approved or pending)
        $overtime = DB::table('overtimes')
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereIn('status', ['approved', 'pending'])
            ->first();

        // Defaults
        $is_overtime = false;
        $status = 'pending overtime';
        $TOTAL_OVERTIME = 0;

        if ($overtime) {
            $is_overtime = true;

            // Only compute hours if approved
            if ($overtime->status === 'approved') {
                $status = 'overtime';
                $approvedHours = (double) $overtime->total_hours;
                $TOTAL_OVERTIME = min($timelogHours, $approvedHours);
            }
        }

        // Convert hours to minutes
        $overtimeMinutes = round($TOTAL_OVERTIME * 60);

        return [
            'is_overtime'   => $is_overtime,
            'overtime_hrs'  => $TOTAL_OVERTIME,
            'overtime_mins'  => $overtimeMinutes,
            'status'        => $status,
        ];
    }

    /**
     * Compute the total tardiness and undertime of an employee for a given date.
     *
     * This function retrieves the assigned shift from the database, determines
     * the employee's effective working schedule based on flexible rules,
     * and calculates tardiness and undertime for both morning and afternoon sessions.
     *
     * Rules:
     * - If the employee clocks in earlier than the earliest time-in, the shift ends
     *   based on the earliest time-in + total working hours.
     * - If the employee clocks in later than the latest time-in, the shift ends
     *   based on the latest time-in + total working hours.
     * - Otherwise, the shift ends based on the employee's actual time-in + total working hours.
     *
     * Tardiness:
     * - Morning tardiness = difference between actual time-in and scheduled latest time-in.
     * - Afternoon tardiness = difference between actual break-in and scheduled break-in.
     *
     * Undertime:
     * - Morning undertime = difference between actual break-out and scheduled break-out.
     * - Afternoon undertime = difference between actual time-out and computed end time.
     *
     * @param array $date  Employee logs containing:
     *                     - time_in, time_out, break_out, break_in, shift_id
     * @return array       [
     *                        'tardiness' => total tardiness in minutes,
     *                        'undertime' => total undertime in minutes,
     *                        'total_ut'  => combined total of tardiness + undertime
     *                     ]
     */
    public function computeTardinessAndUndertime($date)
    {
        $shift = DB::table('shifts')->where('id', $date['shift_id'])->first();
        if (!$shift) return null;

        $workDate = Carbon::parse($date['date']);

        $breakDurationMins = $shift->breaktime_hours * 60;
        $shiftDurationHours = $shift->working_hours + $shift->breaktime_hours; // correctly include break hours

        // Helper to combine reference date with time
        $combineWithDate = fn($time) => $time
            ? Carbon::parse($time)->setDate($workDate->year, $workDate->month, $workDate->day)
            : null;

        // Shift schedule
        $shiftEarliestIn  = $combineWithDate($shift->earliest_time);
        $shiftStart       = $combineWithDate($shift->start_time);
        $shiftBreakOut    = $combineWithDate($shift->break_out_time);
        $shiftBreakIn     = $combineWithDate($shift->break_in_time);
        $shiftEnd         = $combineWithDate($shift->end_time);
        $baseStart        = $shiftStart;

        // Employee logs
        $logIn       = $combineWithDate($date['time_in']);
        $logOut      = $combineWithDate($date['time_out']);
        $logBreakOut = $combineWithDate($date['break_out']);
        $logBreakIn  = $combineWithDate($date['break_in']);

        // Flexible shift adjustment
        if ($shift->is_flexible && $logIn) {
            $baseStart = $logIn->copy();
            if ($shiftEarliestIn && $logIn->lt($shiftEarliestIn)) {
                $baseStart = $shiftEarliestIn;
            } elseif ($shiftStart && $logIn->gt($shiftStart)) {
                $baseStart = $shiftStart;
            }

            $shiftEnd = $baseStart->copy()->addHours($shiftDurationHours);
        }

        // Compute tardiness & undertime
        $amTardiness   = $this->computeTardiness($logIn, $shiftStart);

        $amUndertime = ($logOut && $logOut->lte($shiftBreakOut))
            ? $this->computeUndertime($logOut, $shiftBreakOut, $baseStart)
            : $this->computeUndertime($logBreakOut, $shiftBreakOut, $baseStart);

        $pmTardiness = $this->computeTardiness($logBreakIn, $shiftBreakIn);
        $pmUndertime = $this->computeUndertime($logOut, $shiftEnd, $shiftBreakIn);

        $totalTardiness = $amTardiness + $pmTardiness;
        $totalUndertime = $amUndertime + $pmUndertime;
        $totalLostMinutes = $totalTardiness + $totalUndertime;

        $actualWorkMinutes = 0;
        $remark = null;

        // Special halfday cases
        if ($logIn && $shiftBreakIn && $logIn->gte($shiftBreakIn)) {
            // Late in → halfday
            $actualWorkMinutes = max(0, $logIn->diffInMinutes($logOut));
            $remark = 'halfday';
            $totalTardiness = max(0, $totalTardiness - $breakDurationMins);

        } elseif ($logOut && $shiftBreakOut && $logOut->lte($shiftBreakOut)) {
            // Early out → halfday
            $actualWorkMinutes = max(0, $logIn->diffInMinutes($logOut));
            $remark = 'halfday';
            $totalTardiness = max(0, $totalTardiness - $breakDurationMins);
        } else {
            // Normal full day
            $actualWorkMinutes = max(0, ($baseStart->diffInMinutes($shiftEnd) - $breakDurationMins - $totalLostMinutes));
        }

        $totalLostHours   = floor($totalLostMinutes / 60);
        $remainingMinutes = $totalLostMinutes % 60;

        $data = [
            'am_tardiness'     => $amTardiness,
            'am_undertime'     => $amUndertime,
            'pm_tardiness'     => $pmTardiness,
            'pm_undertime'     => $pmUndertime,
            'total_tardiness'  => $totalTardiness,
            'total_undertime'  => $totalUndertime,
            'actual_work_mins' => $actualWorkMinutes,
            'lost_minutes'     => $totalLostMinutes,
            'lost_hours'       => sprintf('%02d:%02d', $totalLostHours, $remainingMinutes),
            'remark'           => $remark,
        ];

        // dd($data);

        return $data;
    }

    
    /**
     * Compute tardiness in minutes.
     *
     * Tardiness is the number of minutes the employee arrived later than the expected time.
     * If the actual time is earlier or equal to the expected time, tardiness is 0.
     *
     * Example:
     *   Expected: 9:00 AM
     *   Actual:   9:15 AM
     *   Result:   15 minutes tardiness
     *
     * @param \Carbon\Carbon $actual   The employee's actual time in
     * @param \Carbon\Carbon $expected The scheduled/expected time in
     * @return int                     Number of tardiness minutes (0 if not late)
     */
    protected function computeTardiness(?Carbon $actual, ?Carbon $expected): int
    {
        return ($actual && $expected && $actual->gt($expected)) 
            ? $expected->diffInMinutes($actual) 
            : 0;
    }

    /**
     * Compute undertime in minutes.
     *
     * Undertime is the number of minutes the employee left earlier than the expected time.
     * If the actual time is later or equal to the expected time, undertime is 0.
     *
     * Example:
     *   Expected: 6:00 PM
     *   Actual:   5:45 PM
     *   Result:   15 minutes undertime
     *
     * @param \Carbon\Carbon $actual   The employee's actual time out
     * @param \Carbon\Carbon $expected The scheduled/expected time out
     * @return int                     Number of undertime minutes (0 if not early)
     */

    protected function computeUndertime(?Carbon $actual, ?Carbon $expected, ?Carbon $in): int
    {
        if (!$actual || !$expected) return 0;

        // Normalize times (remove seconds)
        $actual   = $actual->copy()->seconds(0);
        $expected = $expected->copy()->seconds(0);
        if ($in) {
            $in = $in->copy()->seconds(0);
        }

        // Ensure actual is not before shift start
        if ($in && $actual->lt($in)) {
            $actual = $in;
        }

        // Compute undertime in minutes (ignoring seconds)
        return $actual->lt($expected)
            ? $expected->diffInMinutes($actual)
            : 0;
    }


    /**
     * Calculate the difference between two overtime timestamps.
     *
     * This function accepts two time strings in the format `h:i A` (e.g., "06:30 PM"),
     * converts them into Carbon instances, and calculates the total overtime worked.
     *
     * - Computes the total difference in minutes.
     * - Separates the total into whole hours and remaining minutes.
     * - Provides both separated values and a combined decimal value for convenience.
     *
     * @param  string  $overtimeIn   The overtime start time (e.g., "06:30 PM").
     * @param  string  $overtimeOut  The overtime end time (e.g., "09:45 PM").
     * @return array{
     *     overtime_in: \Carbon\Carbon,     // Parsed Carbon instance of overtime start
     *     overtime_out: \Carbon\Carbon,    // Parsed Carbon instance of overtime end
     *     hours: int,                       // Whole hours of overtime
     *     minutes: int,                     // Remaining minutes after extracting hours
     *     combined_hours: float             // Total overtime in decimal hours (e.g., 3.75)
     * }
     */
    public function overtimeDifference($overtimeIn, $overtimeOut)
    {
        // Convert input strings to Carbon instances
        $overtimeIn = Carbon::createFromFormat('h:i A', $overtimeIn);
        $overtimeOut = Carbon::createFromFormat('h:i A', $overtimeOut);

        // Get difference in minutes
        $minutes = $overtimeIn->diffInMinutes($overtimeOut);

        // Convert to decimal hours, rounded to 2 decimal places
        $decimal = round($minutes / 60, 2);

        // HH:MM formatted string
        $formatted = $this->convertToHoursAndMinutes($minutes);
        
        return [
            'overtime_in'    => $overtimeIn,
            'overtime_out'   => $overtimeOut,
            'total_overtime' => $formatted,
            'decimal'        => $decimal
        ];
    }

    protected function convertToHoursAndMinutes($minutes)
    {
        $hours = intdiv($minutes, 60);        // whole hours
        $remainderMinutes = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remainderMinutes);
    }

    /**
     * Get rest days from a work schedule
     *
     * @param object|array $schedule
     * @return array
     */
    public function getRestDays($scheduleId)
    {
        $schedule = DB::table('work_schedule')->find($scheduleId);

        if (!$schedule) return [];

        $days = [
            'Monday'    => $schedule->is_monday,
            'Tuesday'   => $schedule->is_tuesday,
            'Wednesday' => $schedule->is_wednesday,
            'Thursday'  => $schedule->is_thursday,
            'Friday'    => $schedule->is_friday,
            'Saturday'  => $schedule->is_saturday,
            'Sunday'    => $schedule->is_sunday,
        ];

        $restDays = [];
        foreach ($days as $dayName => $value) {
            if ($value == 0) { // 0 = rest day
                $restDays[] = $dayName;
            }
        }

        return $restDays;
    }

    public function getHolidays($date_today)
    {
        return DB::table('holidays')
            ->whereDate('date', $date_today)
            ->where('is_active', true)
            ->first();
    }

    public function checkSuspension($date_today)
    {
        
    }

}
