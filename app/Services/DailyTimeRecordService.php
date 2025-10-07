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
        $TOTAL_LEAVES = $TOTAL_OBS = $TOTAL_UT = $TOTAL_HOURS = 0;
        $TOTAL_OVERTIME = $TOTAL_ABSENT = $TOTAL_HOLIDAY = $TOTAL_SUSPENSION = 0;

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
                $remarks[] = 'holiday';
                $TOTAL_HOLIDAY++;
                $holiday_no_work_rate = $holiday->no_work_rate;
                $holiday_work_rate = $holiday->work_rate;

                if (!$empty_log) {
                    $double = $holiday_work_rate;
                } else {
                    $double = $holiday_no_work_rate;

                    if (!isset($shiftsCache[$shift_id])) {
                        $shiftsCache[$shift_id] = DB::table('shifts')->find($shift_id);
                    }
                    $shift = $shiftsCache[$shift_id];

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
                    $computedData[] = $this->timelogs_services->insertNoData($is_leave ? $leave_status : $remarks, $userId);
                    continue;
                }

                if (!$is_future && !$is_leave && !$is_restday) {
                    $remarks[] = 'absent';
                    $TOTAL_ABSENT++;
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId);
                    continue;
                }

                if ($leave_status === 'pending leave') {
                    $computedData[] = $this->timelogs_services->insertNoData($remarks, $userId);
                    continue;
                }
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

            if (!$timeInCarbon || !$timeOutCarbon) {
                $remarks[] = 'incomplete log';
                $computedData[] = [
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

                if ($ot_data['is_overtime']) {
                    $TOTAL_OVERTIME += $ot_data['overtime_mins'];
                    $ot_mins = $ot_data['overtime_mins'];
                    $remarks[] = $ot_data['status'];
                }
            }

            /** ---------------- TARDINESS & UNDERTIME ---------------- **/
            $tar_under = $this->timelogs_services->computeTardinessAndUndertime($date);
            $TOTAL_UT += $tar_under['lost_minutes'];
            if ($tar_under['remark']) $remarks[] = $tar_under['remark'];

            /** ---------------- COMPUTE TOTALS ---------------- **/
            $total_time_work = $tar_under['actual_work_mins'];
            $TOTAL_HOURS += $total_time_work;
            $paid_hours = $total_time_work + $ot_mins;

            /** ---------------- FINAL DATA ROW ---------------- **/
            $computedData[] = [
                'user_id'           => $userId,
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break,
                'overtime'          => $overtime,
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'ot_mins'           => $ot_mins,
                'total_time_work'   => $total_time_work,
                'doble'             => $double,
                'late_undertime'    => $tar_under['lost_minutes'],
                'paid_hours'        => $paid_hours,
                'remarks'           => $remarks,
            ];
        }

        /** ---------------- SUMMARY ---------------- **/
        $summary = [
            ['label' => 'Total HRS',        'value' => intval($TOTAL_HOURS / 60) . ' HRS'],
            ['label' => 'Overtime',         'value' => $TOTAL_OVERTIME . ' MINS'],
            ['label' => 'Late / Undertime', 'value' => $TOTAL_UT . ' MINS'],
            ['label' => 'Absent',           'value' => $TOTAL_ABSENT . ' Days'],
            ['label' => 'Leaves',           'value' => $TOTAL_LEAVES . ' Day' . ($TOTAL_LEAVES != 1 ? 's' : '')],
            ['label' => 'Holiday',          'value' => $TOTAL_HOLIDAY . ' Day' . ($TOTAL_HOLIDAY != 1 ? 's' : '')],
            ['label' => 'Suspensions',      'value' => $TOTAL_SUSPENSION],
        ];

        return [
            'computedData' => $computedData,
            'summary'      => $summary,
        ];
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