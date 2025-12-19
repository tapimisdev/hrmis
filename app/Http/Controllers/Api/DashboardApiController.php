<?php

namespace App\Http\Controllers\Api;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Admin\Services\ApplicationController;

class DashboardApiController extends Controller
{
    public function metrics()
    {
        $employeeModel = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no');

        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();

        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $leaveApplications = DB::table('leave_applications as la')
            ->leftJoin('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
            ->whereDate('ld.date', now()->toDateString())
            ->select('la.name', DB::raw('COUNT(DISTINCT la.employee_no) as total'))
            ->groupBy('la.name')
            ->get()
            ->pluck('total', 'name') 
            ->toArray();

        $totalEmployees = (clone $employeeModel)->count() ?? 0;

        $activeEmployees = (clone $employeeModel)
            ->where('account_status', 'active')
            ->count() ?? 0;

        $activeWorkforcePercent = $totalEmployees > 0
            ? round(($activeEmployees / $totalEmployees) * 100, 2)
            : 0;

        $resignedThisMonth = (clone $employeeModel)
            ->where('account_status', 'resigned')
            ->whereBetween('ei.updated_at', [$startOfMonth, $endOfMonth])
            ->count() ?? 0;

        $resignedLastMonth = (clone $employeeModel)
            ->where('account_status', 'resigned')
            ->whereBetween('ei.updated_at', [$lastMonthStart, $lastMonthEnd])
            ->count() ?? 0;

        $averageEmployees = ($totalEmployees + $activeEmployees) / 2;

        $attritionRate = $averageEmployees > 0
            ? round(($resignedThisMonth / $averageEmployees) * 100, 2)
            : 0;

        $attritionRateLastMonth = $averageEmployees > 0
            ? round(($resignedLastMonth / $averageEmployees) * 100, 2)
            : 0;

        $attritionRateChange = $attritionRateLastMonth > 0
            ? round((($attritionRate - $attritionRateLastMonth) / $attritionRateLastMonth) * 100, 2)
            : ($attritionRate > 0 ? 100 : 0);

        $newHires = (clone $employeeModel)
            ->whereMonth('ei.date_hired_organization', $currentMonth)
            ->whereYear('ei.date_hired_organization', $currentYear)
            ->count() ?? 0;

        $upcomingBirthdays = (clone $employeeModel)
            ->whereRaw("DATE_FORMAT(ep.birthday, '%m-%d') BETWEEN ? AND ?", [
                $startOfWeek->format('m-d'),
                $endOfWeek->format('m-d'),
            ])
            ->count() ?? 0;

        $averageTenure = (clone $employeeModel)
            ->whereNotNull('ei.date_hired_organization')
            ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, ei.date_hired_organization, CURDATE())) as avg_years')
            ->value('avg_years') ?? 0;

        $averageTenure = round($averageTenure, 2);

        // fetch birthdays 
        $birthdayList = $this->birthdays();
        $attendancesList = $this->attendances();
        $employment_pie = $this->employment_types();
        $employee_movement = $this->employee_movement();

        return response()->json([
            'cards' => [
                [
                    'name' => 'Total Employees',
                    'value' => $totalEmployees,
                    'subValue' => '↑ ' . $newHires . ' hired • ↓ ' . $resignedThisMonth . ' resigned',
                    'icon' => 'fa-solid fa-users text-blue-500',
                ],

                [
                    'name' => 'Active Employees',
                    'value' => $activeEmployees,
                    'subValue' => $activeWorkforcePercent . '% workforce',
                    'icon' => 'fa-solid fa-user-check text-green-500',
                ],

                [
                    'name' => 'On Leave Today',
                    'value' => is_array($leaveApplications)
                        ? ($leaveApplications['total'] ?? count($leaveApplications))
                        : $leaveApplications,
                    'subValue' => is_array($leaveApplications)
                        ? (($leaveApplications['vacation'] ?? 0) . ' Vacation • ' . ($leaveApplications['sick'] ?? 0) . ' Sick')
                        : 'Today',
                    'icon' => 'fa-solid fa-plane-departure text-orange-500',
                ],

                [
                    'name' => 'Upcoming Birthdays',
                    'value' => is_countable($upcomingBirthdays)
                        ? count($upcomingBirthdays)
                        : $upcomingBirthdays,
                    'subValue' => 'This Week 🎂',
                    'icon' => 'fa-solid fa-cake-candles text-pink-500',
                ],

                [
                    'name' => 'Attrition Rate',
                    'value' => $attritionRate . '%',
                    'subValue' => $attritionRateChange . ' vs last month',
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
                    'value' => $averageTenure . ' yrs',
                    'subValue' => 'Company-wide',
                    'icon' => 'fa-solid fa-hourglass-half text-indigo-500',
                ],
            ],
            'birthdays' => $birthdayList,
            'attendances' => $attendancesList,
            'employment_types' => $employment_pie,
            'employee_movement' => $employee_movement,
        ]);
    }

    public function birthdays()
    {
        $today = now();

        return DB::table('employee_personal')
            ->select('employee_no', 'firstname', 'lastname', 'birthday', 'profile')
            ->whereMonth('birthday', $today->month)
            ->orderByRaw('DAY(birthday)')
            ->get()
            ->map(function ($row) {

                // profile image
                if (!empty($row->profile)) {
                    $image = Storage::url(
                        'uploads/employees/' . $row->employee_no . '/profile/' . $row->profile
                    );
                } else {
                    $image = 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
                        . '&background=random&color=fff&font-size=0.4&font-weight=bold';
                }

                return [
                    'name'     => trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')),
                    'birthday' => $row->birthday, // YYYY-MM-DD
                    'image'    => $image,
                ];
            })
            ->values();
    }

    private function attendances()
    {
        $now = Carbon::now();

        /**
         * Get last 5 WEEKDAYS (excluding Sat & Sun)
         */
        $dates = collect();
        $i = 0;

        while ($dates->count() < 5) {
            $date = $now->copy()->subDays($i);

            if ($date->isWeekday()) {
                $dates->push($date->toDateString());
            }

            $i++;
        }

        // Oldest → Newest
        $dates = $dates->reverse()->values();

        /**
         * Fetch Time-In logs only
         */
        $timelogs = DB::table('timelogs as t')
            ->leftJoin('shifts as s', 't.shift_id', '=', 's.id')
            ->whereIn(DB::raw('DATE(t.date_time)'), $dates)
            ->where('t.fn', FnEnum::TimeIn->value)
            ->select(
                DB::raw('DATE(t.date_time) as date'),
                't.employee_no',
                't.date_time',
                's.start_time'
            )
            ->get();

        $allEmployeesCount = DB::table('employee_information')
                ->where('account_status', 'active')
                ->count();

        $labels = [];
        $onTimeData = [];
        $lateData = [];

        /**
         * Process per day
         */
        foreach ($dates as $date) {
            $recordsForDay = $timelogs->where('date', $date);

            $onTimeCount = 0;
            $lateCount = 0;

            foreach ($recordsForDay as $record) {

                if (!$record->start_time) {
                    continue;
                }

                $timeIn = Carbon::parse($record->date_time);
                $expectedTime = Carbon::parse($date . ' ' . $record->start_time);

                if ($timeIn->lessThanOrEqualTo($expectedTime)) {
                    $onTimeCount++;
                } else {
                    $lateCount++;
                }
            }

            $labels[] = Carbon::parse($date)->format('D');
            $onTimeData[] = $onTimeCount;
            $lateData[] = $lateCount;
        }

        return [
            'labels' => $labels,
            'total_employees' => $allEmployeesCount,
            'datasets' => [
                [
                    'label' => 'On-Time',
                    'backgroundColor' => '#4CAF50',
                    'data' => $onTimeData,
                ],
                [
                    'label' => 'Late',
                    'backgroundColor' => '#F44336',
                    'data' => $lateData,
                ]
            ]
        ];
    }

    private function employment_types() {

        $labels = [];
        $datasets = [];

        $employees = DB::table('employee_information as ei')
            ->leftJoinSub(
                DB::table('employee_organization as eo')
                    ->select('eo.employee_no', 'eo.employment_type_id')
                    ->whereIn('eo.id', function ($query) {
                        $query->selectRaw('MAX(id)')
                            ->from('employee_organization')
                            ->groupBy('employee_no');
                    }),
                'latest_org',
                'ei.employee_no',
                '=',
                'latest_org.employee_no'
            )
            ->leftJoin('employment_types as etype', 'latest_org.employment_type_id', '=', 'etype.id')
            ->select('etype.name', DB::raw('COUNT(ei.employee_no) as total'))
            ->groupBy('etype.name')
            ->get();

        foreach ( $employees as $emp ) {
            $labels[] = $emp->name;
            $datasets[] = $emp->total;
        }
   
        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];

    }

    private function employee_movement()
    {
        $now = Carbon::now();

        // Get months from January until current month
        $months = collect(range(1, $now->month))->map(function ($m) use ($now) {
            $date = Carbon::create($now->year, $m, 1);
            return [
                'month' => $date->format('M'),
                'year' => $date->year,
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ];
        });

        $employeeModel = DB::table('employee_information');

        $data = $months->map(function ($m) use ($employeeModel) {
            $newHires = (clone $employeeModel)
                ->whereBetween('date_hired_organization', [$m['start'], $m['end']])
                ->count();

            $resigned = (clone $employeeModel)
                ->whereBetween('date_resigned', [$m['start'], $m['end']])
                ->count();

            return [
                'month' => $m['month'],
                'new_hires' => $newHires,
                'resigned' => $resigned,
            ];
        });

        $labels = [];
        $hires = [];
        $resignations = [];

        foreach ($data as $d) {
            $labels[] = $d['month'];
            $hires[] = $d['new_hires'];
            $resignations[] = $d['resigned'];
        }

        return [
            'labels' => $labels,
            'hires' => $hires,
            'resignations' => $resignations,
        ];
    }

}
