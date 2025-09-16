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

        // Loop through each date to compute attendance
        foreach ($dates as $date) {
            $remarks = [];                 // Initialize remarks array for this date
            $is_future = false;            // Flag to check if date is today/future
            $empty_time_in_and_out = (empty($date['time_in']) && empty($date['time_out']));
            $ot_hrs = 0;

            // Parse date string into Carbon object
            $logDate = Carbon::parse($date['date']);
            $dayName = $logDate->format('l');  // Get day name (e.g., Monday)

            // Check if the log date is today
            $is_same_day = $today->isSameDay($logDate);

            // If the date doesn't have a work schedule, assign default
            if(is_null($date['work_schedule_id'])) {
                $date['work_schedule_id'] = $weeklySchedule_id;
            }

            // Get rest days for this work schedule
            $date_work_schedule = $this->timelogs_services->getRestDays($date['work_schedule_id']); 

            // Mark as 'restday' if the log date is a rest day
            if (in_array($dayName, $date_work_schedule) && $empty_time_in_and_out) {
                $remarks[] = 'restday';
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
                if(!$is_leave) {
                    // Mark as absent if no leave
                    $remarks[] = 'absent';
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
            $timeInCarbon   = !empty($date['time_in']) 
                                ? Carbon::parse($date['time_in'])->format('h:i A') 
                                : null;

            $timeOutCarbon  = !empty($date['time_out']) 
                                ? Carbon::parse($date['time_out'])->format('h:i A') 
                                : null;

            $breakOutCarbon = !empty($date['break_out']) 
                                ? Carbon::parse($date['break_out'])->format('h:i A') 
                                : null;

            $breakInCarbon  = !empty($date['break_in']) 
                                ? Carbon::parse($date['break_in'])->format('h:i A') 
                                : null;

            $overtimeOutCarbon = !empty($date['overtime_out']) 
                                ? Carbon::parse($date['overtime_out'])->format('h:i A') 
                                : null;

            $overtimeInCarbon  = !empty($date['overtime_in']) 
                                ? Carbon::parse($date['overtime_in'])->format('h:i A') 
                                : null;

            // Combine break times if both are present
            $break = ($breakOutCarbon && $breakInCarbon)
                ? $breakOutCarbon . ' to ' . $breakInCarbon
                : null;
            
            // Combine break times if both are present
            $overtime = ($overtimeOutCarbon && $overtimeInCarbon)
                ? $overtimeInCarbon . ' to ' . $overtimeOutCarbon
                : null;
            // ================== END TIME PARSING ==================

            // ================== START CHECK OVERTIME ==================

            if(!is_null($overtime)) {
                $timelog_overtime_computed = $this->timelogs_services->overtimeDifference($overtimeInCarbon, $overtimeOutCarbon);

                $overtime_data = $this->timelogs_services->checkOvertime($logDate, $userId, $timelog_overtime_computed);

                if($overtime_data['is_overtime']) {
                    $ot_hrs = $overtime_data['overtime_hrs'];
                    $remarks[] = $overtime_data['status'];
                }
            }

            // ================== END CHECK OVERTIME ==================

            // Append computed data for this date
            $computedData[] = [
                'user_id'           => $userId,
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break,
                'overtime'          => $overtime,
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'ot_hrs'            => $ot_hrs ?? 0,
                'total_paid_hrs'    => $date['total_paid_hrs'] ?? 0,
                'doble'             => $date['doble'] ?? 0,           // Possibly double pay
                'late_undertime'    => $date['late_undertime'] ?? 0,   // Late/Undertime minutes
                'remarks'           => $remarks
            ];
        }

        // Return all computed attendance data
        return $computedData;
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