<?php

namespace App\Http\Controllers\Api;

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

        return response()->json([
            'total_employees' => [
                'count' => $totalEmployees,
                'new' => $newHires,
                'resigned' => $resignedThisMonth,
            ],
            'active_employees' => [
                'count' => $activeEmployees,
                'percentage_of_workforce' => $activeWorkforcePercent,
            ],
            'on_leave' => $leaveApplications,
            'upcoming_birthdays' => $upcomingBirthdays,
            'attrition_rate' => [
                'current' => $attritionRate,
                'change_from_last_month' => $attritionRateChange,
            ],
            'average_tenure' => $averageTenure,
            'training_completion' => 0,
        ]);
    }

    public function birthdays()
    {
        $today = now();

        $birthdays = DB::table('employee_personal')
            ->select('employee_no', 'firstname', 'lastname', 'birthday')
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->get();

        return response()->json($birthdays);
    }

    public function attendances()
    {

        $now = Carbon::now();

        $dates = collect(range(0, 4))->map(fn($i) => $now->copy()->subDays($i)->toDateString());

        $timelogs = DB::table('timelogs as t')
            ->leftJoin('shifts as s', 't.shift_id', '=', 's.id')
            ->whereIn(DB::raw('DATE(t.date_time)'), $dates)
            ->where('t.fn', 0) 
            ->select(
                DB::raw('DATE(t.date_time) as date'),
                't.employee_no',
                't.date_time',
                's.start_time'
            )
            ->get();

        $attendanceStats = [];

        foreach ($dates as $date) {
            $recordsForDay = $timelogs->where('date', $date);

            $onTimeCount = 0;
            $lateCount = 0;

            foreach ($recordsForDay as $record) {
                $timeIn = Carbon::parse($record->date_time)->format('H:i:s');
                $startTime = $record->start_time;

                if ($timeIn <= $startTime) {
                    $onTimeCount++;
                } else {
                    $lateCount++;
                }
            }

            $date = strtolower(Carbon::parse($date)->format('D'));

            $attendanceStats[$date] = [
                'on_time' => $onTimeCount,
                'late' => $lateCount,
            ];
        }

        return response()->json($attendanceStats);
    }

    public function employment_types() {
       dd('asdasd');
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

        return response()->json($employees);

    }

    public function employee_movement()
    {
        $now = Carbon::now();

        // Get the last 7 months including current
        $months = collect(range(6, 0))->map(function ($i) use ($now) {
            $date = $now->copy()->subMonths($i);
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
                'month' => $m['month'] . ' ' . $m['year'],
                'new_hires' => $newHires,
                'resigned' => $resigned,
            ];
        });

        return response()->json($data);
    }


}
