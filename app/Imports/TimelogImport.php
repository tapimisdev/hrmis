<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TimelogImport implements ToCollection
{
    protected $shift_id;
    protected $work_schedule_id;

    public function __construct($shift_id, $work_schedule_id)
    {
        $this->shift_id = $shift_id;
        $this->work_schedule_id = $work_schedule_id;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Skip header or empty rows
            if (
                $index === 0 ||
                empty($row[0]) ||
                strtolower(trim((string) $row[0])) === 'user_id'
            ) {
                continue;
            }

            $userId      = (int) $row[0];
            $employeeNo  = $row[1] ?? null;
            $dateTime    = Carbon::parse($row[2])->format('Y-m-d H:i'); // drop seconds
            $fn          = (int) ($row[3] ?? 0);
            $dateOnly    = Carbon::parse($dateTime)->toDateString();

            // Check if a timelog already exists (same user, fn, and date)
            $existing = DB::table('timelogs')
                ->where('user_id', $userId)
                ->where('fn', $fn)
                ->whereDate('date_time', $dateOnly)
                ->first();

            if ($existing) {
                // Update existing record
                DB::table('timelogs')
                    ->where('id', $existing->id)
                    ->update([
                        'employee_no'      => $employeeNo,
                        'date_time'        => $dateTime,
                        'shift_id'         => $this->shift_id,
                        'work_schedule_id' => $this->work_schedule_id,
                        'updated_at'       => now(),
                    ]);
            } else {
                // Insert new record
                DB::table('timelogs')->insert([
                    'user_id'          => $userId,
                    'employee_no'      => $employeeNo,
                    'date_time'        => $dateTime,
                    'shift_id'         => $this->shift_id,
                    'work_schedule_id' => $this->work_schedule_id,
                    'fn'               => $fn,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

}
