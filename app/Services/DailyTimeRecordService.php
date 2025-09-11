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
        return $this->compute($mapPeriodToTimelogs);
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

    protected function compute($dates)
    {
        $computedData = [];

        $weeklySchedule = DB::table('work_schedule')->first();
        $shift = DB::table('shifts')->first();

        foreach ($dates as $date) {
            $remarks = [];

            // If no time-in/out, push Absent and skip further processing
            if (empty($date['time_in']) || empty($date['time_out'])) {
                $computedData[] = $this->insertNoData('Absent');
                continue;
            }

            // Safe parsing to avoid Carbon error if null
            $timeInCarbon   = Carbon::parse($date['time_in'])->format('h:i A');
            $timeOutCarbon  = Carbon::parse($date['time_out'])->format('h:i A');
            $breakOutCarbon = !empty($date['break_out']) ? Carbon::parse($date['break_out'])->format('h:i A') : null;
            $breakInCarbon  = !empty($date['break_in']) ? Carbon::parse($date['break_in'])->format('h:i A') : null;

            $break = ($breakOutCarbon && $breakInCarbon)
                ? $breakOutCarbon . ' to ' . $breakInCarbon
                : null;

            $computedData[] = [
                'time_in'           => $timeInCarbon,
                'time_out'          => $timeOutCarbon,
                'break'             => $break,
                'shift_id'          => $date['shift_id'],
                'work_schedule_id'  => $date['work_schedule_id'],
                'overtime'          => $date['overtime'] ?? 0,
                'total_paid_hrs'    => $date['total_paid_hrs'] ?? 0,
                'doble'             => $date['doble'] ?? 0,
                'late_undertime'    => $date['late_undertime'] ?? 0,
                'remarks'           => $remarks
            ];
        }

        return $computedData;
    }

    public function insertNoData($remark)
    {
        return [
            'time_in'           => null,
            'time_out'          => null,
            'break'             => null,
            'overtime'          => 0,
            'total_paid_hrs'    => 0,
            'doble'             => 0,
            'late_undertime'    => 0,
            'remarks'           => [$remark]
        ];
    }


   

}