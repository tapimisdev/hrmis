<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class DailyTimeRecordService {

    protected $timelogs_services;

    public function __construct(TimelogsServices $timelogs_services)
    {
        $this->timelogs_services = $timelogs_services;
    }

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
                'time_out'  => null,
                'shift_id'  => null,
                'work_schedule_id'  => null,
            ]);
        });

        // Return as a collection or array
        return $dateLogs;
    }

    protected function compute($dates, $userId)
    {
        // Array to hold the final computed attendance data
        $computedData = [];

        // Today's date for comparison with log dates
        $today = Carbon::today();

        // Fetch default user info like shift and work schedule
        $defaultInformation = $this->getUserDefault($userId);

        $shift_id = $defaultInformation->shift_id;             // Default shift ID of the user
        $weeklySchedule_id = $defaultInformation->work_schedule_id; // Default weekly schedule ID

        // Counters for leaves and official business
        $TOTAL_LEAVES = 0;
        $TOTAL_OBS = 0;
        $TOTAL_UT = 0;
        $TOTAL_HOURS = 0;
        $TOTAL_OVERTIME = 0;
        $TOTAL_ABSENT = 0;
        $TOTAL_HOLIDAY = 0;
        $TOTAL_SUSPENSION = 0;

        // Loop through each date to compute attendance
        foreach ($dates as $date) {
            $remarks = [];                 // Initialize remarks array for this date
            $is_future = false;            // Flag to check if date is today/future
            $empty_time_in_and_out = (empty($date['time_in']) && empty($date['time_out']));
            $ot_mins = 0;
            $total_time_work = 0;
            $double = 1;
            // Parse date string into Carbon object
            $logDate = Carbon::parse($date['date']);
            $dayName = $logDate->format('l');  // Get day name (e.g., Monday)
            
            // Check if the log date is today
            $is_same_day = $today->isSameDay($logDate);
            $is_restday = false;

            $holiday = $this->timelogs_services->getHolidays($date['date']);

            if($holiday) {
                $remarks[] = 'holiday';
                $holiday_no_work_rate = $holiday->no_work_rate;
                $holiday_work_rate = $holiday->work_rate;
                $holiday_overtime = $holiday->overtime_rate;

                // If no time in/out on holiday, mark as holiday
                $TOTAL_HOLIDAY += 1;

                if(!$empty_time_in_and_out) {
                    $double = $holiday_work_rate;
                } else {
                    $double = $holiday_no_work_rate;
                    $shift = DB::table('shifts')->where('id', $shift_id )->first();

                    $computedData[] = [
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

            // If the date doesn't have a work schedule, assign default
            if(is_null($date['work_schedule_id'])) {
                $date['work_schedule_id'] = $weeklySchedule_id;
            }

            // If the date doesn't have a shift schedule, assign default
            if(is_null($date['shift_id'])) {
                $date['shift_id'] = $shift_id;
            }

            // Get rest days for this work schedule
            $date_work_schedule = $this->timelogs_services->getRestDays($date['work_schedule_id']); 

            // Mark as 'restday' if the log date is a rest day
            if (in_array($dayName, $date_work_schedule) && $empty_time_in_and_out) {
                $remarks[] = 'restday';
                $is_restday = true;
            }

            // Mark as 'restday' if the log date is a rest day
            if (in_array($dayName, $date_work_schedule) && !$empty_time_in_and_out) {
                $remarks[] = 'restday ot';
            }

            
            // Flag future dates and mark 'today'
            if($logDate->greaterThan($today) || $is_same_day) {
                $is_future = true;
                if($is_same_day) {
                    $remarks[] = 'today';
                }
            }

            // ================== LEAVE AND ABSENT BLOCK ==================
            // Check if user has leave on this date
            $leave = $this->timelogs_services->checkIfLeave($date, $userId);
            $is_leave = $leave['is_leave'];
            $leave_status = $leave['status'];

            if($is_leave) {
                $TOTAL_LEAVES += 1;        // Increment total leaves
                $remarks[] = $leave_status; // Add leave status to remarks
            }
            
            // Case: No time-in/out, future date, and has leave
            if ($empty_time_in_and_out && $is_future && $is_leave) {
                $computedData[] = $this->timelogs_services->insertNoData($leave_status, $userId);
                continue;
            }

            // Case: No time-in/out, past date
            if ($empty_time_in_and_out && !$is_future) {
                if(!$is_leave && !$is_restday) {
                    // Mark as absent if no leave
                    $remarks[] = 'absent';
                    $TOTAL_ABSENT += 1;
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId);
                    continue;
                } else {
                    // If leave is pending, still mark it
                    if($leave_status === 'pending leave') {
                        $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId);
                        continue;
                    }
                }
                
            }

            // Case: No time-in/out and future date without leave
            if ($empty_time_in_and_out && $is_future) {
                $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId);
                continue;
            }
            // ================== END LEAVE AND ABSENT ==================

            // ================== TIME PARSING BLOCK ==================
            // Safely parse times to avoid errors if null
            $timeInCarbon       = $this->timelogs_services->parseTime($date['time_in']);
            $timeOutCarbon      = $this->timelogs_services->parseTime($date['time_out']);
            $breakOutCarbon     = $this->timelogs_services->parseTime($date['break_out']);
            $breakInCarbon      = $this->timelogs_services->parseTime($date['break_in']);
            $overtimeOutCarbon  = $this->timelogs_services->parseTime($date['overtime_out']);
            $overtimeInCarbon   = $this->timelogs_services->parseTime($date['overtime_in']);

            // Break time formatting using switch-case
            switch (true) {
                case ($breakOutCarbon && $breakInCarbon):
                    $break = $breakOutCarbon . ' to ' . $breakInCarbon;
                    break;
                case ($breakOutCarbon):
                    $break = $breakOutCarbon . ' to -- : --';
                    break;
                case ($breakInCarbon):
                    $break = '-- : -- to ' . $breakInCarbon;
                    break;
                default:
                    $break = null;
                    break;
            }

            // Overtime time formatting using switch-case
            switch (true) {
                case ($overtimeInCarbon && $overtimeOutCarbon):
                    $overtime = $overtimeInCarbon . ' to ' . $overtimeOutCarbon;
                    break;
                case ($overtimeInCarbon):
                    $overtime = $overtimeInCarbon . ' to  -- : --';
                    break;
                case ($overtimeOutCarbon):
                    $overtime = ' -- : -- to ' . $overtimeOutCarbon;
                    break;
                default:
                    $overtime = null;
                    break;
            }

            if(!$timeInCarbon || !$timeOutCarbon) {
                $remarks[] = 'incomplete log';

                    $computedData[] = [
                        'user_id'           => $userId,
                        'time_in'           => $timeInCarbon,
                        'time_out'          => $timeOutCarbon,
                        'break'             => $break   ,
                        'overtime'          => $overtime,   
                        'shift_id'          => $date['shift_id'],
                        'work_schedule_id'  => $date['work_schedule_id'],
                        'ot_mins'           => 0,
                        'total_time_work'   => 0,
                        'doble'             => 0,
                        'late_undertime'    => 0,
                        'paid_hours'        => 0,
                        'remarks'           => $remarks,
                    ];
                    continue;
            }
            // ================== END TIME PARSING ==================

            // ================== START CHECK OVERTIME ==================
            if ($overtimeInCarbon && $overtimeOutCarbon) {
                // Only compute if both overtime in/out exist
                $timelog_overtime_computed = $this->timelogs_services->overtimeDifference($overtimeInCarbon, $overtimeOutCarbon);

                $overtime_data = $this->timelogs_services->checkOvertime($logDate, $userId, $timelog_overtime_computed);

                if ($overtime_data['is_overtime']) {
                    $TOTAL_OVERTIME += $overtime_data['overtime_mins'];
                    $ot_mins = $overtime_data['overtime_mins'];
                    $remarks[] = $overtime_data['status'];
                }
            } else {
                // If either is missing, you can still store remarks or leave OT as null
                $ot_mins = 0;
            }

            // ================== END CHECK OVERTIME ==================

            // ================== START CHECK TARDINESS AND UNDERTIME ==================

            $computed_tar_underime = $this->timelogs_services->computeTardinessAndUndertime($date);
            $TOTAL_UT += $computed_tar_underime['lost_minutes'];

            if(!is_null($computed_tar_underime['remark'])) {
                $remarks[] = $computed_tar_underime['remark'];
            }

            // ================== END CHECK TARDINESS AND UNDERTIME ==================

            $total_time_work = $computed_tar_underime['actual_work_mins'];
            $TOTAL_HOURS += $total_time_work;

            $paid_hours = $total_time_work + $ot_mins;

            // Append computed data for this date
            $computedData[] = [
                'user_id'           => $userId,
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break   ,
                'overtime'          => $overtime,   
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'ot_mins'           => $ot_mins ?? 0,
                'total_time_work'   => $total_time_work ?? 0,
                'doble'             => $double,          
                'late_undertime'    => $computed_tar_underime['lost_minutes'] ?? 0,
                'paid_hours'        => $paid_hours ?? 0,
                'remarks'           => $remarks
            ];
        }

        $summary = [
            ['label' => 'Total HRS',         'value' => intval($TOTAL_HOURS / 60) . ' HRS'],
            ['label' => 'Overtime',          'value' => $TOTAL_OVERTIME . ' MINS'],
            ['label' => 'Late / Undertime',  'value' => $TOTAL_UT . ' MINS'],
            ['label' => 'Absent',            'value' => $TOTAL_ABSENT . ' Days'],
            ['label' => 'Leaves',            'value' => $TOTAL_LEAVES . ' Day' . ($TOTAL_LEAVES != 1 ? 's' : '')],
            ['label' => 'Holiday',           'value' => ($TOTAL_HOLIDAY ?? 0) . ' Day' . (($TOTAL_HOLIDAY ?? 0) != 1 ? 's' : '')],
            ['label' => 'Suspensions',       'value' => ($TOTAL_SUSPENSION ?? 0)],
        ];

        $data = [
            'computedData' => $computedData,
            'summary'      => $summary
        ];

        // Return all computed attendance data
        return $data;
    }


    protected function getUserDefault($user_id)
    {
        return  DB::table('employee_information as ei')
                ->leftJoin('employee_shift_work_schedule as sws', 'ei.employee_no', '=', 'sws.employee_no')
                ->where('ei.user_id', $user_id)
                ->select('ei.employee_no', 'sws.shift_id', 'sws.work_schedule_id')
                ->first();                
    }

}