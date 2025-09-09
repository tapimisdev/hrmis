<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\throwException;

class TimelogsServices {

    public function getTimeLogs($userId)
    {
        $timelogs = DB::table('timelogs')
            ->where('user_id', $userId)
            ->orderBy('date_time', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->date_time)->toDateString();
            });

        $result = [];

        foreach ($timelogs as $date => $logs) {
            $valid = $this->getValidLogs($logs);

            $result[] = [
                'date'       => $date,
                'time_in'    => $valid['in']->date_time ?? null,
                'break_out'  => $valid['break_out']->date_time ?? null,
                'break_in'   => $valid['break_in']->date_time ?? null,
                'time_out'   => $valid['out']->date_time ?? null,
            ];
        }

        // sort dates descending (latest first)
        return collect($result)->sortByDesc('date')->values();
    }

    public function getValidLogs($logs)
    {
        $duplicateThreshold = 10; // minutes; adjust if you want stricter/looser duplicate detection

        // Normalize & sort
        $logs = collect($logs)->sortBy('date_time')->values();

        // 1) Collapse near-duplicates (keep earliest of duplicates)
        $filtered = collect();
        foreach ($logs as $log) {
            if ($filtered->isEmpty()) {
                $filtered->push($log);
                continue;
            }

            $last = $filtered->last();
            $lastTime = \Carbon\Carbon::parse($last->date_time);
            $currTime = \Carbon\Carbon::parse($log->date_time);

            if ($currTime->diffInSeconds($lastTime) < $duplicateThreshold) {
                // Considered a duplicate (too close) — skip the later one
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
        ];

        if ($n === 0) {
            return $validLogs;
        }

        if ($n === 1) {
            $validLogs['in'] = $filtered->get(0);
            return $validLogs;
        }

        if ($n === 2) {
            // assume in + out
            $validLogs['in'] = $filtered->get(0);
            $validLogs['break_out'] = $filtered->get(1);
            return $validLogs;
        }

        if ($n === 3) {
            // assume in, break_out, break_in
            $validLogs['in'] = $filtered->get(0);
            $validLogs['break_out'] = $filtered->get(1);
            $validLogs['break_in'] = $filtered->get(2);
            return $validLogs;
        }

        // n >= 4: use first, second, second-last, last
        $validLogs['in'] = $filtered->get(0);
        $validLogs['break_out'] = $filtered->get(1);
        $validLogs['break_in'] = $filtered->get($n - 2);
        $validLogs['out'] = $filtered->get($n - 1);

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

        if (    !empty($current_timelog['breakIn']) &&
                !empty($current_timelog['breakIn']) &&
                !empty($current_timelog['breakIn']) &&
                !empty($current_timelog['breakIn'])
            ) {

            throw new \Exception('Ano ba kumpleto na eh!!');
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
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

    }


}
