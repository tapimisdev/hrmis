<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

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

        return collect($result);
    }

    public function getValidLogs($logs)
    {
        $duplicateThreshold = 5; // minutes; adjust if you want stricter/looser duplicate detection

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

            if ($currTime->diffInMinutes($lastTime) <= $duplicateThreshold) {
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
            $validLogs['out'] = $filtered->get(1);
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



}
