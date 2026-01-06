<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyTimeRecordService {

    protected $timelogs_services;

    public function __construct(TimelogsServices $timelogs_services)
    {
        $this->timelogs_services = $timelogs_services;
    }

    /**
     * Retrieves and computes the Daily Time Record (DTR) for a user within a specified date range.
     *
     * This function performs the following steps:
     * 1. Extracts the user ID and date range from the payload.
     * 2. Generates a date period from startDate to endDate.
     * 3. Fetches the user's time logs within the period.
     * 4. Maps each date in the period to the corresponding time log (if any).
     * 5. Calls the compute() method to calculate detailed work log information, including
     *    attendance, leaves, overtime, tardiness, and total hours worked.
     *
     * @param array $payload {
     *     The input data containing:
     *     @type int|string 'user_id'   The ID of the user.
     *     @type string 'startDate'     Start date of the DTR period (Y-m-d format).
     *     @type string 'endDate'       End date of the DTR period (Y-m-d format).
     * }
     *
     * @return array Returns the computed DTR data, including:
     *   - 'computedData': Array of daily work log records with time, overtime, remarks, etc.
     *   - 'summary': Array of aggregated totals (hours worked, leaves, overtime, absences, etc.).
     */
    public function getDTR(array $payload)
    {
        $userId = $payload['user_id'];
        $startDate = $payload['startDate'];
        $endDate   = $payload['endDate'];
        $period = CarbonPeriod::create($startDate, $endDate);

        $timelogs = $this->timelogs_services->getTimeLogsWithPeriod($userId, $startDate, $endDate);

        $mapPeriodToTimelogs = $this->mapPeriodToTimelogs($period, $timelogs);
        return $this->compute($mapPeriodToTimelogs, $userId);
    }

    /**
     * Maps a date period to the corresponding time logs for a user.
     *
     * This function ensures that every date in the given period has an associated time log,
     * defaulting to null values for dates without logs. It simplifies downstream DTR computation
     * by providing a consistent structure for each day.
     *
     * @param \Carbon\CarbonPeriod $period The range of dates to map.
     * @param array|\Illuminate\Support\Collection $timelogs Array or collection of time logs, each containing 'date' and time entries.
     *
     * @return \Illuminate\Support\Collection Returns a collection of daily time log records, each containing:
     *   - 'date': The date in Y-m-d format.
     *   - 'time_in': Time in, or null if missing.
     *   - 'break_out': Break start time, or null if missing.
     *   - 'break_in': Break end time, or null if missing.
     *   - 'time_out': Time out, or null if missing.
     *   - 'shift_id': Shift ID, or null if missing.
     *   - 'work_schedule_id': Work schedule ID, or null if missing.
     */
    private function mapPeriodToTimelogs($period, $timelogs)
    {
        // Key timelogs by date for easy lookup
        $timelogsByDate = collect($timelogs)->keyBy('date');

        // Map all dates to logs, defaulting to nulls if missing
        $dateLogs = collect($period)->map(function ($date) use ($timelogsByDate) {
            $d = $date->format('Y-m-d');

            return $timelogsByDate->get($d, [
                'date'      => $d,
                'time_in'   => null,
                'break_out' => null,
                'break_in'  => null,
                'overtime_in'  => null,
                'overtime_out'  => null,
                'time_out'  => null,
                'shift_id'  => null,
                'work_schedule_id'  => null,
            ]);
        });

        // Return as a collection or array
        return $dateLogs;
    }

    /**
     * Computes the detailed work log and payroll data for a given user over a set of dates.
     *
     * This function processes each date to determine:
     * - Attendance status (present, absent, incomplete log)
     * - Leaves (approved, pending)
     * - Holidays and rest days
     * - Suspensions (whole day or half day)
     * - Overtime
     * - Tardiness and undertime
     * - Total work hours and paid hours
     *
     * It caches repeated database lookups (shifts, holidays, rest days) for efficiency.
     *
     * @param array $dates Array of date records containing time in/out, breaks, and overtime info.
     * @param int|string $userId The ID of the user for whom the computation is performed.
     *
     * @return array Returns an array containing:
     *   - 'computedData': Array of computed daily records including time logs, total worked minutes, overtime, remarks, etc.
     *   - 'summary': Array of aggregated totals, including total hours, incomplete logs, leaves, overtime, tardiness, absences, holidays, and suspensions.
     */
    protected function compute($dates, $userId)
    {
        $computedData = [];
        $today = Carbon::today();

        // Fetch default user info once
        $defaultInfo = $this->getUserDefault($userId);
        $shift_id = $defaultInfo->shift_id;
        $weeklySchedule_id = $defaultInfo->work_schedule_id;

        // Cache results to avoid redundant DB hits
        $shiftsCache = [];
        $holidaysCache = [];
        $restDaysCache = [];

        // Totals
        $TOTAL_INCOMPLETE_LOGS = 0;
        $TOTAL_PENDING_LEAVES = 0;
        $TOTAL_LEAVES = $TOTAL_OBS = $TOTAL_UT = $TOTAL_HOURS = 0;
        $TOTAL_OVERTIME = $TOTAL_ACTUAL_PRESENCE = $TOTAL_ABSENT = $TOTAL_HOLIDAY = $TOTAL_SUSPENSION = 0;
        $DOUBLE_EXCESS = 0;

        $IS_OT_PAY_EMPLOYEE = false;

        foreach ($dates as $date) {
            $remarks = [];
            $is_future = false;
            $empty_log = empty($date['time_in']) && empty($date['time_out']);
            $ot_mins = $total_time_work = 0;
            $double = 1;

            $logDate = Carbon::parse($date['date']);
            $dayName = $logDate->format('l');
            $is_same_day = $today->isSameDay($logDate);
            $is_restday = false;

            /** ---------------- HOLIDAY CHECK ---------------- **/
            if (!isset($holidaysCache[$date['date']])) {
                $holidaysCache[$date['date']] = $this->timelogs_services->getHolidays($date['date']);
            }
            $holiday = $holidaysCache[$date['date']];

            if ($holiday) {
                $remarks[] = $holiday->type . ' ' . 'holiday' ;
                $TOTAL_HOLIDAY++;
                $holiday_no_work_rate = $holiday->no_work_rate;
                $holiday_work_rate = $holiday->work_rate;

                if (!$empty_log) {
                    $double = $holiday_work_rate;
                    $DOUBLE_EXCESS += $holiday_work_rate - 1;
                    // Log::info("Holiday work day for user ID: {$userId} on {$date['date']} with rate: {$holiday_work_rate - 1}");
                } else {
                    $double = $holiday_no_work_rate;
                    $DOUBLE_EXCESS += $holiday_no_work_rate - 1;
                    // Log::info("Holiday no work day for user ID: {$userId} on {$date['date']} with rate: {$holiday_no_work_rate - 1}");

                    if (!isset($shiftsCache[$shift_id])) {
                        $shiftsCache[$shift_id] = DB::table('shifts')->find($shift_id);
                    }
                    $shift = $shiftsCache[$shift_id];

                    $computedData[] = [
                        'date'              => $logDate,
                        'user_id'           => $userId,
                        'time_in'           => null,
                        'time_out'          => null,
                        'break'             => null,
                        'overtime'          => null,
                        'shift_id'          => $date['shift_id'],
                        'work_schedule_id'  => $date['work_schedule_id'],
                        'ot_mins'           => 0,
                        'total_time_work'   => $shift->working_hours * 60,
                        'doble'             => $double,
                        'late_undertime'    => 0,
                        'paid_hours'        => $shift->working_hours * 60,
                        'remarks'           => $remarks,
                    ];
                    continue;
                }
            }

            /** ---------------- SHIFT AND WORK SCHEDULE ---------------- **/
            $date['work_schedule_id'] ??= $weeklySchedule_id;
            $date['shift_id'] ??= $shift_id;

            if (!isset($restDaysCache[$date['work_schedule_id']])) {
                $restDaysCache[$date['work_schedule_id']] = $this->timelogs_services->getRestDays($date['work_schedule_id']);
            }
            $date_work_schedule = $restDaysCache[$date['work_schedule_id']];

            /** ---------------- RESTDAY CHECK ---------------- **/
            if (in_array($dayName, $date_work_schedule)) {
                $remarks[] = $empty_log ? 'restday' : 'restday ot';
                $is_restday = true;
            }

            /** ---------------- CHECK AND GET SUSPENSION ---------------- **/
            $suspension = $this->timelogs_services->checkSuspension($date['date']);

            /** ---------------- FUTURE/TODAY CHECK ---------------- **/
            if ($logDate->greaterThan($today) || $is_same_day) {
                $is_future = true;
                if ($is_same_day) $remarks[] = 'today';
            }

            /** ---------------- LEAVE CHECK ---------------- **/
            $leave = $this->timelogs_services->checkIfLeave($date, $userId);
            $is_leave = $leave['is_leave'];
            $leave_status = $leave['status'];

            if ($is_leave) {
                $TOTAL_LEAVES++;
                $remarks[] = $leave_status;
            }

            if ($empty_log) {
                if ($is_future) {
                    $computedData[] = $this->timelogs_services->insertNoData($is_leave ? $leave_status : $remarks, $userId, $date['date']);
                    continue;
                }

                if(!$is_future && $suspension['is_suspended'] && $suspension['type'] === 'whole_day') {
                    $remarks[] = 'suspension' . ' ' . ucfirst(str_replace('_', ' ', $suspension['type']));
                    $TOTAL_SUSPENSION++;
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId, $date['date']);
                    continue;
                }

                 // If halfday suspended
                if(!$is_future && $suspension['is_suspended'] && $suspension['type'] === 'half_day') {
                    $remarks[] = 'suspension' . ' ' . ucfirst(str_replace('_', ' ', $suspension['shift']));
                    $TOTAL_SUSPENSION++;
                    $computedData[] = [
                        'date'              => $date['date'],
                        'user_id'           => $userId,
                        'time_in'           => null,
                        'time_out'          => null,
                        'break'             => null,
                        'overtime'          => null,
                        'shift_id'          => null,
                        'work_schedule_id'  => null,
                        'ot_mins'           => 0,
                        'total_time_work'    => 240,
                        'doble'             => $double,
                        'late_undertime'    => 240,
                        'paid_hours'        => 0,
                        'remarks'           => $remarks,
                    ];
                    continue;
                }

                if (!$is_future && !$is_leave && !$is_restday) {
                    $remarks[] = 'absent';
                    $TOTAL_ABSENT++;
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId, $date['date']);
                    continue;
                }

                if ($leave_status === 'pending leave' && $is_leave) {
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId, $date['date']);
                    $TOTAL_PENDING_LEAVES++;
                    continue;
                }
            } else {
                $TOTAL_ACTUAL_PRESENCE++;
            }

            /** ---------------- PARSE TIMES ---------------- **/
            $timeInCarbon      = $this->timelogs_services->parseTime($date['time_in']);
            $timeOutCarbon     = $this->timelogs_services->parseTime($date['time_out']);
            $breakOutCarbon    = $this->timelogs_services->parseTime($date['break_out']);
            $breakInCarbon     = $this->timelogs_services->parseTime($date['break_in']);
            $otInCarbon        = $this->timelogs_services->parseTime($date['overtime_in']);
            $otOutCarbon       = $this->timelogs_services->parseTime($date['overtime_out']);

            $break = match (true) {
                $breakOutCarbon && $breakInCarbon => "$breakOutCarbon to $breakInCarbon",
                $breakOutCarbon => "$breakOutCarbon to -- : --",
                $breakInCarbon => "-- : -- to $breakInCarbon",
                default => null,
            };

            $overtime = match (true) {
                $otInCarbon && $otOutCarbon => "$otInCarbon to $otOutCarbon",
                $otInCarbon => "$otInCarbon to -- : --",
                $otOutCarbon => "-- : -- to $otOutCarbon",
                default => null,
            };

            if ((!$timeInCarbon || !$timeOutCarbon) && !$is_restday) {
                $remarks[] = 'incomplete log';
                $TOTAL_INCOMPLETE_LOGS++;

                $computedData[] = [
                    'date'              => $logDate,
                    'user_id'          => $userId,
                    'time_in'          => $timeInCarbon,
                    'time_out'         => $timeOutCarbon,
                    'break'            => $break,
                    'overtime'         => $overtime,
                    'shift_id'         => $date['shift_id'],
                    'work_schedule_id' => $date['work_schedule_id'],
                    'ot_mins'          => 0,
                    'total_time_work'  => 0,
                    'doble'            => 0,
                    'late_undertime'   => 0,
                    'paid_hours'       => 0,
                    'remarks'          => $remarks,
                ];
                continue;
            }

            /** ---------------- OVERTIME CHECK ---------------- **/
            if ($otInCarbon && $otOutCarbon) {
                $computed_ot = $this->timelogs_services->overtimeDifference($otInCarbon, $otOutCarbon);
                $ot_data = $this->timelogs_services->checkOvertime($logDate, $userId, $computed_ot);
                
                if (!empty($ot_data['is_overtime'])) {
                    $ot_mins = $ot_data['overtime_mins'];
                    $TOTAL_OVERTIME += $ot_data['overtime_mins'];
                    $remarks[] = $ot_data['status'];
                    $IS_OT_PAY_EMPLOYEE = $ot_data['is_ot_pay_employee'];
                }
            }

            /** ---------------- TARDINESS & UNDERTIME ---------------- **/
            $tar_under = $this->timelogs_services->computeTardinessAndUndertime($date, $suspension);
            $TOTAL_UT += $tar_under['lost_minutes'];
            if ($tar_under['remark']) $remarks[] = $tar_under['remark'];

            $required_to_work_in_mins = $tar_under['required_to_work_in_mins'];

            /** ---------------- COMPUTE TOTALS ---------------- **/
            $total_time_work = $tar_under['actual_work_mins'];

            if($IS_OT_PAY_EMPLOYEE) {
                $paid_hours = $total_time_work + $ot_mins;
            } else {
                $paid_hours = $total_time_work;
            }

            $TOTAL_HOURS += $paid_hours;

            /** ---------------- FINAL DATA ROW ---------------- **/
            $computedData[] = [
                'date'              => $logDate,
                'user_id'           => $userId,
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break,
                'overtime'          => $overtime,
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'ot_mins'           => $ot_mins,
                'total_time_work'   => $required_to_work_in_mins - $tar_under['lost_minutes'],
                'doble'             => $double,
                'late_undertime'    => max(0, $tar_under['lost_minutes']),
                'paid_hours'        => $paid_hours,
                'remarks'           => $remarks,
            ];
        }

        /** ---------------- SUMMARY ---------------- **/
        $summary = [
            [
                'label' => 'Total HRS',
                'value' => intval($TOTAL_HOURS / 60) . ' HRS ' . ($TOTAL_HOURS % 60) . ' MINS',
                'actual_value' => intval($TOTAL_HOURS / 60),
                'actual_minutes' => $TOTAL_HOURS % 60,
            ],
            ['label' => 'Incomplete Logs',  'value' => $TOTAL_INCOMPLETE_LOGS, 'actual_value' => $TOTAL_INCOMPLETE_LOGS ],
            ['label' => 'Pending Leaves',   'value' => $TOTAL_PENDING_LEAVES, 'actual_value' => $TOTAL_PENDING_LEAVES ],
            ['label' => 'Overtime',         'value' => $TOTAL_OVERTIME . ' MINS', 'actual_value' => $TOTAL_OVERTIME ],
            ['label' => 'Late / Undertime', 'value' => $TOTAL_UT . ' MINS', 'actual_value' => $TOTAL_UT ],
            ['label' => 'Absent',           'value' => $TOTAL_ABSENT . ' Days', 'actual_value' => $TOTAL_ABSENT ],
            ['label' => 'Leaves',           'value' => $TOTAL_LEAVES . ' Day' . ($TOTAL_LEAVES != 1 ? 's' : ''), 'actual_value' => $TOTAL_LEAVES ],
            ['label' => 'Holiday',          'value' => $TOTAL_HOLIDAY . ' Day' . ($TOTAL_HOLIDAY != 1 ? 's' : ''), 'actual_value' => $TOTAL_HOLIDAY ],
            ['label' => 'Suspensions',      'value' => $TOTAL_SUSPENSION, 'actual_value' => $TOTAL_SUSPENSION ],
            ['label' => 'Excess',           'value' => number_format($DOUBLE_EXCESS, 2), 'actual_value' => number_format($DOUBLE_EXCESS, 2)],
        ];

        $payroll_value = [
            'total_hours'        => intval($TOTAL_HOURS / 60),
            'incomplete_logs'    => $TOTAL_INCOMPLETE_LOGS,
            'pending_leaves'     => $TOTAL_PENDING_LEAVES,
            'overtime'           => $TOTAL_OVERTIME,
            'late_undertime'     => $TOTAL_UT,
            'absent'             => $TOTAL_ABSENT,
            'leaves'             => $TOTAL_LEAVES,
            'holiday'            => $TOTAL_HOLIDAY,
            'suspensions'        => $TOTAL_SUSPENSION,
            'excess'             => $DOUBLE_EXCESS,
            'actual_presence'    => $TOTAL_ACTUAL_PRESENCE,
        ];

        return [
            'computedData' => $computedData,
            'summary'      => $summary,
            'payroll_value'      => $payroll_value,
        ];
    }

    /**
     * Retrieves the default shift and work schedule for a given user.
     *
     * This function fetches the employee number, assigned shift ID, and work schedule ID
     * from the database for the specified user. It is used as the baseline for computing
     * the user's Daily Time Record (DTR) when no specific date-level overrides exist.
     *
     * @param int|string $user_id The ID of the user.
     *
     * @return object|null Returns an object containing:
     *   - 'employee_no': The employee number.
     *   - 'shift_id': The default shift ID assigned to the employee.
     *   - 'work_schedule_id': The default work schedule ID assigned to the employee.
     *   Returns null if the user is not found.
     */
    protected function getUserDefault($user_id)
    {
        return  DB::table('employee_information as ei')
                ->leftJoin('employee_shift_work_schedule as sws', 'ei.employee_no', '=', 'sws.employee_no')
                ->where('ei.user_id', $user_id)
                ->select('ei.employee_no', 'sws.shift_id', 'sws.work_schedule_id')
                ->first();                
    }

}