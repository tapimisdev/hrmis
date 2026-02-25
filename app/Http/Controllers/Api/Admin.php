<?php

namespace App\Http\Controllers\Api;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\EventService;

class Admin extends Controller
{

    public $EventService;

    public function __construct(EventService $EventService) {
        $this->EventService = $EventService;
    }

    /**
     * DASHBOARD METRICS
     */
    public function metrics()
    {
        /** -------------------------
         * Dates
         * ------------------------*/
        $now = now();
        $today = $now->toDateString();

        $currentMonth = $now->month;
        $currentYear  = $now->year;

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth   = $now->copy()->endOfMonth();
        $startOfWeek  = $now->copy()->startOfWeek();
        $endOfWeek    = $now->copy()->endOfWeek();

        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        /** -------------------------
         * Base Employee Query
         * ------------------------*/
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no');

        /** -------------------------
         * Leave Today
         * ------------------------*/
        $leaveToday = DB::table('leave_applications as la')
            ->join('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
            ->whereDate('ld.date', $today)
            ->where('ld.isActive', true)
            ->select('la.name', DB::raw('COUNT(DISTINCT la.employee_no) as total'))
            ->groupBy('la.name')
            ->pluck('total', 'name')
            ->toArray();

        /** -------------------------
         * Offset Today
         * ------------------------*/
        $offsetToday = DB::table('offset_applications as oa')
            ->join('offset_dates as od', 'oa.id', '=', 'od.offset_application_id')
            ->whereDate('od.date', $today)
            ->where('od.isActive', true)
            ->where('oa.status', 'approved')
            ->distinct('oa.employee_no')
            ->count('oa.employee_no');

        $soToday = DB::table('special_order_applications as oa')
            ->join('special_order_dates as od', 'oa.id', '=', 'od.special_order_application_id')
            ->whereDate('od.date', $today)
            ->where('od.isActive', true)
            ->where('oa.status', 'approved')
            ->distinct('oa.employee_no')
            ->count('oa.employee_no');

        $obsToday = DB::table('obs_applications as oa')
            ->join('obs_dates as od', 'oa.id', '=', 'od.obs_application_id')
            ->whereDate('od.date', $today)
            ->where('od.isActive', true)
            ->where('oa.status', 'approved')
            ->distinct('oa.employee_no')
            ->count('oa.employee_no');

        $announcementToday = DB::table('events_announcements')
            ->whereDate('posted_on', $today)
            ->count();

        $suspensionsToday = DB::table('suspension as s')
            ->leftJoin('suspension_dates as sd', 'sd.suspension_id', 's.id')
            ->whereDate('date', $today)
            ->count();

        $holidayToday = DB::table('holidays as s')
            ->whereDate('date', $today)
            ->count();

        /** -------------------------
         * Employee Counts
         * ------------------------*/
        $totalEmployees = (clone $employees)->count();
        $activeEmployees = (clone $employees)
            ->where('account_status', 'active')
            ->count();

        $activeWorkforcePercent = $totalEmployees
            ? round(($activeEmployees / $totalEmployees) * 100, 2)
            : 0;

        /** -------------------------
         * Attrition
         * ------------------------*/
        $resignedThisMonth = (clone $employees)
            ->where('account_status', 'resigned')
            ->whereBetween('ei.updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $resignedLastMonth = (clone $employees)
            ->where('account_status', 'resigned')
            ->whereBetween('ei.updated_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $averageEmployees = ($totalEmployees + $activeEmployees) / 2;

        $attritionRate = $averageEmployees
            ? round(($resignedThisMonth / $averageEmployees) * 100, 2)
            : 0;

        $attritionRateLastMonth = $averageEmployees
            ? round(($resignedLastMonth / $averageEmployees) * 100, 2)
            : 0;

        $attritionRateChange = $attritionRateLastMonth
            ? round((($attritionRate - $attritionRateLastMonth) / $attritionRateLastMonth) * 100, 2)
            : ($attritionRate > 0 ? 100 : 0);

        /** -------------------------
         * Other Metrics
         * ------------------------*/
        $newHires = (clone $employees)
            ->whereMonth('ei.date_hired_organization', $currentMonth)
            ->whereYear('ei.date_hired_organization', $currentYear)
            ->count();

        $upcomingBirthdays = (clone $employees)
            ->whereRaw(
                "DATE_FORMAT(ep.birthday, '%m-%d') BETWEEN ? AND ?",
                [$startOfWeek->format('m-d'), $endOfWeek->format('m-d')]
            )
            ->count();

        $averageTenure = round(
            (clone $employees)
                ->whereNotNull('ei.date_hired_organization')
                ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, ei.date_hired_organization, CURDATE()))')
                ->value(DB::raw('AVG(TIMESTAMPDIFF(YEAR, ei.date_hired_organization, CURDATE()))')) ?? 0,
            2
        );

        /** -------------------------
         * RESPONSE
         * ------------------------*/
        return response()->json([
            'cards' => [
                [
                    'name' => 'Total Employees',
                    'value' => $totalEmployees,
                    'subValue' => "↑ {$newHires} hired • ↓ {$resignedThisMonth} resigned",
                    'icon' => 'fa-solid fa-users text-blue-500',
                ],
                [
                    'name' => 'Active Employees',
                    'value' => $activeEmployees,
                    'subValue' => "{$activeWorkforcePercent}% workforce",
                    'icon' => 'fa-solid fa-user-check text-green-500',
                ],
                [
                    'name' => 'Leave',
                    'value' => array_sum($leaveToday),
                    'subValue' =>
                        ($leaveToday['vacation'] ?? 0) . ' Vacation • ' .
                        ($leaveToday['sick'] ?? 0) . ' Sick',
                    'icon' => 'fa-solid fa-plane-departure text-orange-500',
                ],
                [
                    'name' => 'Offset',
                    'value' => $offsetToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-ghost text-purple-500',
                ],
                [
                    'name' => 'Special Order',
                    'value' => $soToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-car-on text-purple-500',
                ],
                [
                    'name' => 'Pass Slip / OBS ',
                    'value' => $obsToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-torii-gate text-purple-500',
                ],
                [
                    'name' => 'Announcements',
                    'value' => $announcementToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-calendar-days text-purple-500',
                ],
                [
                    'name' => 'Suspensions',
                    'value' => $suspensionsToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-cloud-rain text-purple-500',
                ],
                [
                    'name' => 'Holidays',
                    'value' => $holidayToday,
                    'subValue' => 'Today',
                    'icon' => 'fa-solid fa-calendar-day text-purple-500',
                ],
                [
                    'name' => 'Upcoming Birthdays',
                    'value' => $upcomingBirthdays,
                    'subValue' => 'This Week 🎂',
                    'icon' => 'fa-solid fa-cake-candles text-pink-500',
                ],
                [
                    'name' => 'Attrition Rate',
                    'value' => "{$attritionRate}%",
                    'subValue' => "{$attritionRateChange}% vs last month",
                    'icon' => 'fa-solid fa-chart-line text-red-500',
                ],
                [
                    'name' => 'New Hires',
                    'value' => $newHires,
                    'subValue' => 'This Month',
                    'icon' => 'fa-solid fa-user-plus text-green-600',
                ],
                [
                    'name' => 'Average Tenure',
                    'value' => "{$averageTenure} yrs",
                    'subValue' => 'Company-wide',
                    'icon' => 'fa-solid fa-hourglass-half text-indigo-500',
                ],
            ],
            'birthdays' => $this->birthdays(),
            'attendances' => $this->attendances(),
            'employment_types' => $this->employment_types(),
            'employee_movement' => $this->employee_movement(),
        ]);
    }

    /**
     * BIRTHDAYS
     */
    public function birthdays()
    {
        $month = now()->month;

        return DB::table('employee_personal')
            ->select('employee_no', 'firstname', 'lastname', 'birthday', 'profile')
            ->whereMonth('birthday', $month)
            ->orderByRaw('DAY(birthday)')
            ->get()
            ->map(function ($row) {
                $image = $row->profile
                    ? Storage::url("public/users/{$row->employee_no}/profile-image/{$row->profile}")
                    : 'https://ui-avatars.com/api/?name=' .
                        urlencode(trim("{$row->firstname} {$row->lastname}")) .
                        '&background=random&color=fff&font-size=0.4&font-weight=bold';

                return [
                    'employee_no' => $row->employee_no,
                    'name' => trim("{$row->firstname} {$row->lastname}"),
                    'birthday' => $row->birthday,
                    'image' => $image,
                ];
            })
            ->values();
    }

    /**
     * ATTENDANCES (LAST 5 WEEKDAYS)
     */
    private function attendances()
    {
        $dates = collect();
        $cursor = now();

        while ($dates->count() < 5) {
            if ($cursor->isWeekday()) {
                $dates->push($cursor->toDateString());
            }
            $cursor->subDay();
        }

        $dates = $dates->reverse()->values();

        $timelogs = DB::table('timelogs as t')
            ->leftJoin('shifts as s', 't.shift_id', '=', 's.id')
            ->whereIn(DB::raw('DATE(t.date_time)'), $dates)
            ->where('t.fn', FnEnum::TimeIn->value)
            ->select(
                DB::raw('DATE(t.date_time) as date'),
                't.date_time',
                's.start_time'
            )
            ->get()
            ->groupBy('date');

        $labels = [];
        $onTime = [];
        $late = [];

        foreach ($dates as $date) {
            $onTimeCount = 0;
            $lateCount = 0;

            foreach ($timelogs->get($date, []) as $log) {
                if (!$log->start_time) continue;

                $timeIn = Carbon::parse($log->date_time);
                $expected = Carbon::parse("{$date} {$log->start_time}");

                $timeIn->lte($expected) ? $onTimeCount++ : $lateCount++;
            }

            $labels[] = Carbon::parse($date)->format('D');
            $onTime[] = $onTimeCount;
            $late[] = $lateCount;
        }

        return [
            'labels' => $labels,
            'total_employees' => DB::table('employee_information')->where('account_status', 'active')->count(),
            'datasets' => [
                ['label' => 'On-Time', 'backgroundColor' => '#4CAF50', 'data' => $onTime],
                ['label' => 'Late', 'backgroundColor' => '#F44336', 'data' => $late],
            ]
        ];
    }

    /**
     * EMPLOYMENT TYPES PIE
     */
    private function employment_types()
    {
        $data = DB::table('employee_information as ei')
            ->leftJoinSub(
                DB::table('employee_organization')
                    ->select('employee_no', DB::raw('MAX(id) as id'))
                    ->groupBy('employee_no'),
                'latest',
                'ei.employee_no',
                '=',
                'latest.employee_no'
            )
            ->leftJoin('employee_organization as eo', 'latest.id', '=', 'eo.id')
            ->leftJoin('employment_types as et', 'eo.employment_type_id', '=', 'et.id')
            ->select('et.name', DB::raw('COUNT(*) as total'))
            ->groupBy('et.name')
            ->get();

        return [
            'labels' => $data->pluck('name'),
            'datasets' => $data->pluck('total'),
        ];
    }

    /**
     * EMPLOYEE MOVEMENT
     */
    private function employee_movement()
    {
        $year = now()->year;
        $currentMonth = now()->month;

        $labels = [];
        $hires = [];
        $resignations = [];

        for ($month = 1; $month <= $currentMonth; $month++) {

            $date = Carbon::create($year, $month, 1);

            $start = $date->copy()->startOfMonth();
            $end   = $date->copy()->endOfMonth();

            $labels[] = $date->format('M');

            $hires[] = DB::table('employee_information')
                ->whereBetween('date_hired_company', [$start, $end])
                ->count();

            $resignations[] = DB::table('employee_information')
                ->whereBetween('date_resigned', [$start, $end])
                ->count();
        }
                
        return [
            'labels' => $labels,
            'hires' => $hires,
            'resignations' => $resignations,
        ];
    }

    public function getNotifications(Request $request)
    {
        $data = $this->EventService->getNotifications($request, ['admins', Auth::id()]);
        return response()->json($data);
    }

    public function saveReadNotification(Request $request)
    {
        $data = $this->EventService->saveReadNotification($request);
        return response()->json($data);
    }
    
}
