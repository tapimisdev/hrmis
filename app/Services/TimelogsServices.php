<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\throwException;

class TimelogsServices {

    public function getTimeLogs($userId)
    {
        // Get all logs for the user
        $timelogs = $this->fetchLogs($userId);
        return $this->formatLogs($timelogs);
    }

    public function getTimeLogsWithPeriod($userId, $startDate, $endDate)
    {
        // Get logs within a date range
        $timelogs = $this->fetchLogs($userId, $startDate, $endDate);

        return $this->formatLogs($timelogs);
    }

    /**
     * Fetch timelogs from database
     * @param int $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \Illuminate\Support\Collection
     */
    private function fetchLogs($userId, $startDate = null, $endDate = null)
    {
        $query = DB::table('timelogs')
            ->where('user_id', $userId)
            ->orderBy('date_time', 'asc');

        if ($startDate && $endDate) {
            $query->whereBetween('date_time', [$startDate, $endDate]);
        }

        return $query->get()->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->date_time)->toDateString();
        });
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

    public function getValidLogs($logs)
    {
        $duplicateThreshold = 10; // seconds for collapsing near-duplicates

        // Normalize & sort
        $logs = collect($logs)->sortBy('date_time')->values();

        // Collapse near-duplicates
        $filtered = collect();
        foreach ($logs as $log) {
            if ($filtered->isEmpty()) {
                $filtered->push($log);
                continue;
            }

            $lastTime = \Carbon\Carbon::parse($filtered->last()->date_time);
            $currTime = \Carbon\Carbon::parse($log->date_time);

            if ($currTime->diffInSeconds($lastTime) < $duplicateThreshold) {
                continue;
            }

            $filtered->push($log);
        }

        $n = $filtered->count();

        $validLogs = [
            'in' => null,
            'break_out' => null,
            'break_in' => null,
            'out' => null,
            'overtime_in' => null,
            'overtime_out' => null,
            'shift_id' => $filtered->first()->shift_id ?? null,
            'work_schedule_id' => $filtered->first()->work_schedule_id ?? null,
        ];

        if ($n === 0) return $validLogs;

        // Separate overtime logs
        $normalLogs = $filtered->take(4); // first 4 are normal logs
        $overtimeLogs = $filtered->slice(4)->values(); // everything after 4th is overtime

        // Assign normal in/out/breaks
        $validLogs['in'] = $normalLogs->get(0) ?? null;
        $validLogs['break_out'] = $normalLogs->get(1) ?? null;
        $validLogs['break_in'] = $normalLogs->get(2) ?? null;
        $validLogs['out'] = $normalLogs->get(3) ?? null;

        // Assign overtime
        if ($overtimeLogs->count() === 1) {
            $validLogs['overtime_in'] = $overtimeLogs->first();
        } elseif ($overtimeLogs->count() >= 2) {
            $validLogs['overtime_in'] = $overtimeLogs->first();
            $validLogs['overtime_out'] = $overtimeLogs->last();
        }

        return $validLogs;
    }

    public function getTodaysLogs($user_id = null)
    {
        $user_id = $user_id ?? auth()->user()->id;
        $today = \Carbon\Carbon::now()->toDateString();

        $logs = DB::table('timelogs')
            ->where('user_id', $user_id)
            ->whereDate('date_time', $today)
            ->orderBy('date_time', 'asc')
            ->get();

        $valid = $this->getValidLogs($logs);

        return [
            'date'       => $today,
            'timeIn'     => isset($valid['in']->date_time) 
                ? \Carbon\Carbon::parse($valid['in']->date_time)->format('h:i:s A') 
                : null,
            'breakOut'   => isset($valid['break_out']->date_time) 
                ? \Carbon\Carbon::parse($valid['break_out']->date_time)->format('h:i:s A') 
                : null,
            'breakIn'    => isset($valid['break_in']->date_time) 
                ? \Carbon\Carbon::parse($valid['break_in']->date_time)->format('h:i:s A') 
                : null,
            'timeOut'    => isset($valid['out']->date_time) 
                ? \Carbon\Carbon::parse($valid['out']->date_time)->format('h:i:s A') 
                : null,
            'overtimeIn' => isset($valid['overtime_in']->date_time) 
                ? \Carbon\Carbon::parse($valid['overtime_in']->date_time)->format('h:i:s A') 
                : null,
            'overtimeOut'=> isset($valid['overtime_out']->date_time) 
                ? \Carbon\Carbon::parse($valid['overtime_out']->date_time)->format('h:i:s A') 
                : null,
        ];
    }

    public function getLastLog()
    {
        $user_id = auth()->user()->id;

        return DB::table('timelogs')
            ->where('user_id', $user_id)
            ->orderBy('date_time', 'desc')
            ->first();
    }

    public function straightToTimeOut($payload)
    {
        $current_timelog = $this->getTodaysLogs($payload['user_id']);

        if (empty($current_timelog['timeIn'])) {
            throw new \Exception('No clock in yet.');
        }


        if (    !empty($current_timelog['timeIn']) &&
                !empty($current_timelog['breakOut']) &&
                !empty($current_timelog['breakIn']) &&
                !empty($current_timelog['timeOut'])
            ) {

            throw new \Exception('You are already time out');
        }

        // duplicateThreshold = 10 seconds
        $breakOut = $current_timelog['breakOut'];
        $breakIn = $current_timelog['breakIn'];

        if(empty($current_timelog['breakOut'])) {
            $breakOut = Carbon::parse($payload['date_time'])->subSecond(20);
            DB::table('timelogs')->insert([
                'user_id'     => $payload['user_id'],
                'employee_no' => $payload['employee_no'] ?? null,
                'date_time'   => $breakOut,
                'shift_id'   => 1,
                'work_schedule_id'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if(empty($current_timelog['breakIn'])) {
            $breakIn = Carbon::parse($payload['date_time'])->subSecond(10);
            DB::table('timelogs')->insert([
                'user_id'     => $payload['user_id'],
                'employee_no' => $payload['employee_no'] ?? null,
                'date_time'   => $breakIn,
                'shift_id'   => 1,
                'work_schedule_id'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

    }
}
