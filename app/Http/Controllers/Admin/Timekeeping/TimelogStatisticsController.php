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

    public function analyzeEmployeeTimelogs($data, $startOfMonth, $endOfMonth)
    {
        $results = [];

        $accessedEmployees = [];
        $notAccessedEmployees = [];

        foreach ($data as $employeeId => $logs) {

            // Initialize counters
            $absences = $lates = $undertimes = $leaves = $offsets = $so = $obs = $breakOutInDiscrepancies = 0;

            $absenceDates = $lateDates = $undertimeDates = $leaveDates = $offsetDates = $soDates = $obsDates = $breakOutInDiscrepancyDates = [];
            $undertimeDetails = [];

            // Get employee shift & schedule
            $shiftIds = $logs['logs']->pluck('shift_id')->unique()->filter();
            $workScheduleIds = $logs['logs']->pluck('work_schedule_id')->unique()->filter();

            if ($shiftIds->isEmpty() || $workScheduleIds->isEmpty()) {
                $results[$employeeId] = ['error' => 'No valid shift or schedule'];
                continue;
            }

            $shift = DB::table('shifts')->where('id', $shiftIds->first())->first();
            $workSchedule = DB::table('work_schedule')->where('id', $workScheduleIds->first())->first();

            if (!$shift || !$workSchedule) {
                $results[$employeeId] = ['error' => 'Shift or schedule not found'];
                continue;
            }

            // Dates range
            $startDate = $startOfMonth->copy();
            $endDate = $endOfMonth->copy();

            // --- Approved Applications ---
            $approvedLeaveDates = DB::table('leave_dates')
                ->join('leave_applications', 'leave_dates.leave_application_id', '=', 'leave_applications.id')
                ->where('leave_applications.employee_no', $employeeId)
                ->where('leave_applications.status', 'approved')
                ->where('leave_dates.isActive', true)
                ->whereBetween('leave_dates.date', [$startDate, $endDate])
                ->pluck('leave_dates.date')->toArray();

            $approvedOffsetDates = DB::table('offset_dates')
                ->join('offset_applications', 'offset_dates.offset_application_id', '=', 'offset_applications.id')
                ->where('offset_applications.employee_no', $employeeId)
                ->where('offset_applications.status', 'approved')
                ->where('offset_dates.isActive', true)
                ->whereBetween('offset_dates.date', [$startDate, $endDate])
                ->pluck('offset_dates.date')->toArray();

            $approvedSODates = DB::table('special_order_dates')
                ->join('special_order_applications', 'special_order_dates.special_order_application_id', '=', 'special_order_applications.id')
                ->where('special_order_applications.employee_no', $employeeId)
                ->where('special_order_applications.status', 'approved')
                ->where('special_order_dates.isActive', true)
                ->whereBetween('special_order_dates.date', [$startDate, $endDate])
                ->pluck('special_order_dates.date')->toArray();

            $approvedObsDates = DB::table('obs_dates')
                ->join('obs_applications', 'obs_dates.obs_application_id', '=', 'obs_applications.id')
                ->where('obs_applications.employee_no', $employeeId)
                ->where('obs_applications.status', 'approved')
                ->where('obs_dates.isActive', true)
                ->whereBetween('obs_dates.date', [$startDate, $endDate])
                ->pluck('obs_dates.date')->toArray();

            $excludedDates = array_merge(
                $approvedLeaveDates,
                $approvedOffsetDates,
                $approvedSODates,
                $approvedObsDates
            );

            // --- Portal Access Check ---
            $noLoginEmployee = DB::table('employee_information as ei')
                ->leftJoin('users as u', 'ei.user_id', '=', 'u.id')
                ->where('ei.employee_no', $employeeId)
                ->whereColumn('u.created_at', '=', 'u.updated_at')
                ->count();

            if ($noLoginEmployee == 0) {
                $accessedEmployees[] = $logs['employee'];
            } else {
                $notAccessedEmployees[] = $logs['employee'];
            }

            // --- Generate Expected Working Dates ---
            $expectedWorkingDates = [];
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                if ($workSchedule->{'is_' . $dayOfWeek}) {
                    $expectedWorkingDates[] = $currentDate->format('Y-m-d');
                }
                $currentDate->addDay();
            }

            $leaveDates = array_intersect($approvedLeaveDates, $expectedWorkingDates);
            $offsetDates = array_intersect($approvedOffsetDates, $expectedWorkingDates);
            $soDates = array_intersect($approvedSODates, $expectedWorkingDates);
            $obsDates = array_intersect($approvedObsDates, $expectedWorkingDates);

            $leaves = count($leaveDates);
            $offsets = count($offsetDates);
            $so = count($soDates);
            $obs = count($obsDates);

            $logsByDate = $logs['logs']->keyBy('date');

            // --- DAILY ANALYSIS ---
            foreach ($expectedWorkingDates as $date) {
                $current = Carbon::parse($date);
                if ($current->isToday() || $current->isFuture()) continue;
                if (in_array($date, $excludedDates)) continue;

                $log = $logsByDate->get($date);

                // ABSENCE
                if (!$log || !$log['time_in'] || !$log['time_out']) {
                    $absences++;
                    $absenceDates[] = $date;
                    continue;
                }

                // --- Use computeTardinessAndUndertime ---
                $tardinessData = $this->TimelogsServices->computeTardinessAndUndertime([
                    'date'      => $date,
                    'shift_id'  => $log['shift_id'],
                    'time_in'   => $log['time_in'],
                    'time_out'  => $log['time_out'],
                    'break_out' => $log['break_out'] ?? null,
                    'break_in'  => $log['break_in'] ?? null,
                ]);

                // LATE
                if ($tardinessData['total_tardiness'] > 0) {
                    $lates++;
                    $lateDates[] = $date;
                }

                // UNDERTIME
                if ($tardinessData['total_undertime'] > 0) {
                    $undertimes++;
                    $undertimeDates[] = $date;
                }

                // BREAK DISCREPANCY
                if (($log['break_out'] && !$log['break_in']) || (!$log['break_out'] && $log['break_in'])) {
                    $breakOutInDiscrepancies++;
                    $breakOutInDiscrepancyDates[] = $date;
                }
            }

            // --- STORE RESULTS ---
            $results[$employeeId] = [
                'employee' => $logs['employee'],
                'absences' => $absences,
                'lates' => $lates,
                'undertimes' => $undertimes,
                'leaves' => $leaves,
                'offsets' => $offsets,
                'special_order' => $so,
                'obs' => $obs,
                'breakOutInDiscrepancies' => $breakOutInDiscrepancies,
                'details' => [
                    'absence_dates' => $absenceDates,
                    'late_dates' => $lateDates,
                    'undertime_dates' => $undertimeDates,
                    'leave_dates' => $leaveDates,
                    'offset_dates' => $offsetDates,
                    'special_order_dates' => $soDates,
                    'obs_dates' => $obsDates,
                    'breakOutInDiscrepancy_dates' => $breakOutInDiscrepancyDates,
                ],
            ];
        }

        $results['accessed'] = [
            'count' => count($accessedEmployees),
            'details' => $accessedEmployees,
        ];

        $results['notAccessed'] = [
            'count' => count($notAccessedEmployees),
            'details' => $notAccessedEmployees,
        ];

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
                'topOffset' => $this->findTop($data, 'offsets'),
                'topSO' => $this->findTop($data, 'special_order'),
                'topOBS' => $this->findTop($data, 'obs'),
                'loginAccessed' => $data['accessed'],
                'loginNotAccessed' => $data['notAccessed'],
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
