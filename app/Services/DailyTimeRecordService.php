<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

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

        $weeklySchedule = DB::table('work_schedule')->first();
        $shift = DB::table('shifts')->first();

        $TOTAL_LEAVES = 0;
        $TOTAL_OBS = 0;

        foreach ($dates as $date) {
            $remarks = [];
            $is_future = false;
            $empty_time_in_and_out = (empty($date['time_in']) && empty($date['time_out']));

            $logDate = Carbon::parse($date['date']);

            if($logDate->greaterThan($today)) {
                $is_future = true;
            }

            $leave = $this->checkIfLeave($date, $userId);
            $is_leave = $leave['is_leave'];
            $leave_status = $leave['status'];

            if($is_leave) {
                $TOTAL_LEAVES += 1;
                $remarks[] = $leave_status;
            }
            
            if ($empty_time_in_and_out && $is_future && $is_leave) {
                $computedData[] = $this->insertNoData($leave_status, $userId);
                continue;
            }

            if ($empty_time_in_and_out && !$is_future) {
                $computedData[] = $this->insertNoData('Absent', $userId);
                continue;
            }

            if ($empty_time_in_and_out && $is_future) {
                $computedData[] = $this->insertNoData('', $userId);
                continue;
            }

            // if()

            // Safe parsing to avoid Carbon error if null
            $timeInCarbon   = Carbon::parse($date['time_in'])->format('h:i A');
            $timeOutCarbon  = Carbon::parse($date['time_out'])->format('h:i A');
            $breakOutCarbon = !empty($date['break_out']) ? Carbon::parse($date['break_out'])->format('h:i A') : null;
            $breakInCarbon  = !empty($date['break_in']) ? Carbon::parse($date['break_in'])->format('h:i A') : null;

            $break = ($breakOutCarbon && $breakInCarbon)
                ? $breakOutCarbon . ' to ' . $breakInCarbon
                : null;

            $computedData[] = [
                'user_id'           => $userId,
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break,
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'apply_overtime'    => $date['apply_overtime'] ?? false,
                'overtime'          => $date['overtime'] ?? 0,
                'total_paid_hrs'    => $date['total_paid_hrs'] ?? 0,
                'doble'             => $date['doble'] ?? 0,
                'late_undertime'    => $date['late_undertime'] ?? 0,
                'remarks'           => $remarks
            ];
        }

        return $computedData;
    }

    private function checkIfLeave($date, $userId)
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
            if($leave->status === 'approved') {
                $status = 'leave';
            }
        }

        $data = [
            'is_leave' => $isLeave,
            'status'   => $status
        ];

        return $data;
    }


    public function insertNoData($remark, $userId)
    {
        return [
            'user_id'           => $userId,
            'time_in'           => null,
            'time_out'          => null,
            'break'             => null,
            'shift_id'          => null,
            'work_schedule_id'  => null,
            'apply_overtime'    => false,
            'overtime'          => 0,
            'total_paid_hrs'    => 0,
            'doble'             => 0,
            'late_undertime'    => 0,
            'remarks'           => [$remark],
        ];
    }


   

}