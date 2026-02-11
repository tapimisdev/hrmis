<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\TimelogsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimelogStatisticsController extends Controller
{

    protected $TimelogsServices;

    public function __construct(TimelogsServices $timelogService) {
        $this->TimelogsServices = $timelogService;
    }

    public function getTimelogStats($monthYear = null)
    {

        if(is_null($monthYear)) {
            $monthYear = Carbon::now()->format('Y-m');
        }

        $startOfMonth = Carbon::createFromFormat('Y-m', $monthYear)
            ->startOfMonth()
            ->startOfDay();

        $endOfMonth = Carbon::createFromFormat('Y-m', $monthYear)
            ->endOfMonth()
            ->endOfDay();

        /**
         * 1. Get employees with their LATEST shift & schedule
         */
        $employeeInfos = DB::table('employee_information as ei')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('ei.isDeleted', false)
            ->select(
                'ei.user_id',
                'ei.employee_no',
                'ei.biometrics_id',
                'ep.profile',
                'ep.firstname',
                'ep.lastname',
            )
            ->get();

        $data = [];

        foreach ($employeeInfos as $employee) {
            $data[$employee->employee_no] = [
                'employee' => $employee,
                'logs' => $this->TimelogsServices->getTimeLogsWithPeriod($employee->user_id, $startOfMonth, $endOfMonth)
            ];
        }

        $result = $this->analyzeEmployeeTimelogs($data, $startOfMonth, $endOfMonth);
        return $result;
    }

    public function analyzeEmployeeTimelogs($data, $startOfMonth, $endOfMonth) {
        $results = [];

        foreach ($data as $employeeId => $logs) {
            $absences = 0;
            $lates = 0;
            $undertimes = 0;
            $discrepancies = 0;
            $leaves = 0;
            $offsets = 0;
            $breakOutInDiscrepancies = 0;
            $absenceDates = [];
            $lateDates = [];
            $undertimeDates = [];
            $discrepancyDates = [];
            $leaveDates = [];
            $offsetDates = [];
            $breakOutInDiscrepancyDates = [];

            // Get unique shift_id and work_schedule_id (assume one per employee for simplicity)
            $shiftIds = $logs['logs']->pluck('shift_id')->unique()->filter()->values();
            $workScheduleIds = $logs['logs']->pluck('work_schedule_id')->unique()->filter()->values();
            if ($shiftIds->isEmpty() || $workScheduleIds->isEmpty()) {
                // Skip if no valid shift/schedule
                $results[$employeeId] = ['error' => 'No valid shift or work schedule found'];
                continue;
            }
            $shiftId = $shiftIds->first(); // Use first/most common
            $workScheduleId = $workScheduleIds->first();

            $shift = DB::table('shifts')->where('id', $shiftId)->first();
            $workSchedule = DB::table('work_schedule')->where('id', $workScheduleId)->first();
            if (!$shift || !$workSchedule) {
                $results[$employeeId] = ['error' => 'Shift or work schedule not found'];
                continue;
            }

            // Use the provided month range instead of logs' date range to ensure full coverage
            $startDate = $startOfMonth->copy();
            $endDate = $endOfMonth->copy();

            // Fetch approved leave dates for this employee within the period
            $approvedLeaveDates = DB::table('leave_dates')
                ->join('leave_applications', 'leave_dates.leave_application_id', '=', 'leave_applications.id')
                ->where('leave_applications.employee_no', $employeeId)
                ->where('leave_applications.status', 'approved')
                ->where('leave_dates.isActive', true)
                ->whereBetween('leave_dates.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->pluck('leave_dates.date')
                ->toArray();

            // For offsets, assuming similar table 'offset_dates' and 'offset_applications' with same structure
            $approvedOffsetDates = DB::table('offset_dates')
                ->join('offset_applications', 'offset_dates.offset_application_id', '=', 'offset_applications.id')
                ->where('offset_applications.employee_no', $employeeId)
                ->where('offset_applications.status', 'approved')
                ->where('offset_dates.isActive', true)
                ->whereBetween('offset_dates.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->pluck('offset_dates.date')
                ->toArray();

            // Generate expected working dates based on work_schedule within the full month
            $expectedWorkingDates = [];
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dayOfWeek = strtolower($currentDate->format('l')); // e.g., 'monday'
                if ($workSchedule->{'is_' . $dayOfWeek}) {
                    $expectedWorkingDates[] = $currentDate->format('Y-m-d');
                }
                $currentDate->addDay();
            }

            // Count leaves and offsets within expected working dates
            $leaveDates = array_intersect($approvedLeaveDates, $expectedWorkingDates);
            $leaves = count($leaveDates);
            $offsetDates = array_intersect($approvedOffsetDates, $expectedWorkingDates);
            $offsets = count($offsetDates);

            $excludedDates = array_merge($approvedLeaveDates, $approvedOffsetDates); // Dates not to count as absences

            // Map logs by date for quick lookup
            $logsByDate = $logs['logs']->keyBy('date');

            foreach ($expectedWorkingDates as $date) {
                // Skip future dates for absence checks
                if (Carbon::parse($date)->isToday() || Carbon::parse($date)->isFuture()) {
                    continue;
                }

                $log = $logsByDate->get($date);
                if (!$log) {
                    // Check if date is approved leave or offset
                    if (!in_array($date, $excludedDates)) {
                        // Absence
                        $absences++;
                        $absenceDates[] = $date;
                    }
                    continue;
                }

                // Parse times
                $timeIn = $log['time_in'] ? Carbon::parse($log['time_in']) : null;
                $timeOut = $log['time_out'] ? Carbon::parse($log['time_out']) : null;
                $breakOut = $log['break_out'] ? Carbon::parse($log['break_out']) : null;
                $breakIn = $log['break_in'] ? Carbon::parse($log['break_in']) : null;
                $overtimeIn = $log['overtime_in'] ? Carbon::parse($log['overtime_in']) : null;
                $overtimeOut = $log['overtime_out'] ? Carbon::parse($log['overtime_out']) : null;

                // Considered absent if no clock in or no clock out (but has clock in)
                if (!$timeIn || (!$timeOut && $timeIn)) {
                    $absences++;
                    $absenceDates[] = $date;
                    continue; // Skip further checks for this date (no late, undertime, discrepancy)
                }

                // Break out/in discrepancies: if break_out and break_in are missing
                if (!$breakOut && !$breakIn) {
                    $breakOutInDiscrepancies++;
                    $breakOutInDiscrepancyDates[] = $date;
                }

                // Check late: time_in > shift start_time
                $lateThreshold = Carbon::parse($date . ' ' . $shift->start_time);
                $isLate = $timeIn && $timeIn->greaterThan($lateThreshold);
                if ($isLate) {
                    $lates++;
                    $lateDates[] = $date;
                }

                // Check undertime
                $isUndertime = false;

                // Undertime 1: employee break_out less than shift break_out_time
                if ($shift->break_out_time && $breakOut && $breakOut->lessThan(Carbon::parse($date . ' ' . $shift->break_out_time))) {
                    $isUndertime = true;
                }

                // Undertime 2: Didn't complete 8 hours
                if ($timeIn && $timeOut) {
                    $breakSeconds = 0;
                    if ($breakOut && $breakIn) {
                        $breakSeconds = $breakIn->diffInSeconds($breakOut);
                    }
                    $workedSeconds = $timeOut->diffInSeconds($timeIn) - $breakSeconds;
                    $workedHours = $workedSeconds / 3600;

                    if ($isLate) {
                        // If late, count 8 hours from shift start_time
                        $expectedEnd = Carbon::parse($date . ' ' . $shift->start_time)->addHours($shift->working_hours);
                        if ($timeOut->lessThan($expectedEnd)) {
                            $isUndertime = true;
                        }
                    } else {
                        // If not late, check if worked hours < 8
                        if ($workedHours < $shift->working_hours) {
                            $isUndertime = true;
                        }
                    }
                }

                if ($isUndertime) {
                    $undertimes++;
                    $undertimeDates[] = $date;
                }
            }

            $results[$employeeId] = [
                'employee' => $logs['employee'],
                'absences' => $absences,
                'lates' => $lates,
                'undertimes' => $undertimes,
                'discrepancies' => $discrepancies,
                'leaves' => $leaves,
                'offsets' => $offsets,
                'breakOutInDiscrepancies' => $breakOutInDiscrepancies,
                'details' => [
                    'absence_dates' => $absenceDates,
                    'late_dates' => $lateDates,
                    'undertime_dates' => $undertimeDates,
                    'discrepancy_dates' => $discrepancyDates,
                    'leave_dates' => $leaveDates,
                    'offset_dates' => $offsetDates,
                    'breakOutInDiscrepancy_dates' => $breakOutInDiscrepancyDates,
                ],
            ];
        }

        return $results;
    }

    public function findTop(array $data, string $field, int $limit = 10)
    {
        $items = [];

        foreach ($data as $employeeNo => $item) {
            $value = $item[$field] ?? 0; 

            $items[] = [
                'employee_no' => $employeeNo,
                'value' => $value,
                'data' => $item,
            ];
        }

        usort($items, function ($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        $topItems = array_slice($items, 0, $limit);

        $result = [];
        foreach ($topItems as $item) {
            $result[$item['employee_no']] = $item['data'];
        }

        return $result;
    }

    public function index(Request $request) {

        if($request->ajax()) {
            $monthYear = $request->monthYear;

            $data = $this->getTimelogStats($monthYear);
            $result = [
                'topAbsent' => $this->findTop($data, 'absences'),
                'topUndertime' => $this->findTop($data, 'undertimes'),
                'topLate' => $this->findTop($data, 'lates'),
                'topBreakOutInDiscrepancies' => $this->findTop($data, 'breakOutInDiscrepancies'),
                'topLeave' => $this->findTop($data, 'leaves'),
                'topOffset' => $this->findTop($data, 'offsets')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        }

        $years = [];
        $currentYear = Carbon::now()->year;

        for ($i = 0; $i <= 5; $i++) {
            $years[] = $currentYear - $i;
        }

        $months = collect(range(1, 12))
            ->mapWithKeys(function ($m) {
                return [
                    str_pad($m, 2, '0', STR_PAD_LEFT) => Carbon::create()->month($m)->format('F')
                ];
            })
            ->toArray();

        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        return view('admin.pages.timekeeping.statistics', compact('years', 'months', 'currentMonth', 'currentYear'));

    }
}
