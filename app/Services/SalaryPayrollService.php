<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\map;

class SalaryPayrollService {

    protected $daily_time_record_service;
    private $date;
    private $cutoff;

    private $eligibile;
    private $not_eligibile;

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_salary');

        if (!empty($payload['year'])) {
            $query->whereYear('payroll_date', $payload['year']);
        }

        if (!empty($payload['month'])) {
            $query->whereMonth('payroll_date', $payload['month']);
        }

        if (!empty($payload['cutoff'])) {
            $query->where('cutoff', $payload['cutoff']);
        }

        if (!empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        // dd($query->get());

        return $query->get();
    }

    public function getEligibleEmployees($payload)
    {
        $this->date = $payload['date'];
        $this->cutoff = $payload['cutoff'];

        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('positions', 'eo.position_id', '=', 'positions.id')
            ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('eo.employment_type_id', $payload['employment_type_id'])
            ->select(
                'ep.firstname', 
                'ep.middlename', 
                'ep.lastname', 
                'ep.suffix', 

                'positions.name as position',
                'divisions.name as division',

                'ei.user_id', 
                'ei.employee_no', 
                'ei.account_status')
            ->get();
        
        
        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $emp) {
            $this->checkEligibility($emp);
        }

        $seperatedEmployee = [
            'eligible' => $this->eligibile,
            'not_eligible' => $this->not_eligibile,
        ];

        return $seperatedEmployee;
    }

    private function checkEligibility($employee)
    {
        // Prepare payload
        $payload = array_merge($this->getCutoff(), ['user_id' => $employee->user_id]);
        $dtr = $this->daily_time_record_service->getDTR($payload);

        // Extract summary counts in a clean, reusable way
        $summary = collect($dtr['summary'])->keyBy('label')->map(fn($item) => (int) ($item['value'] ?? 0));

        $incompleteLogs = $summary->get('Incomplete Logs', 0);
        $absentCount    = $summary->get('Absent', 0);
        $pendingLeave   = $summary->get('Pending Leave', 0);

        // Initialize remarks
        $remarks = [];
        $eligibleRemarks = [[
            'value' => $absentCount,
            'text' => "Absent: {$absentCount}",
            'url'  => null
        ]];

        if ($pendingLeave > 0) {
            $eligibleRemarks = [[
                'value' => $pendingLeave,
                'text' => "Leave/s: {$pendingLeave}",
                'url'  => null
            ]];
        }

        // Check account status
        if ($employee->account_status !== 'active') {
            $remarks[] = [
                'text' => 'This Employee is Inactive',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        // Check incomplete logs
        if ($incompleteLogs > 0) {
            $remarks[] = [
                'text' => sprintf(
                    "This Employee %s %d missing log%s",
                    $incompleteLogs === 1 ? 'has' : 'have',
                    $incompleteLogs,
                    $incompleteLogs === 1 ? '' : 's'
                ),
                'url' => route('daily-time-record.index', [
                    'employee_no' => $employee->employee_no,
                    'month' => \Carbon\Carbon::parse($this->date)->format('m'),
                    'year' => \Carbon\Carbon::parse($this->date)->format('Y'),
                ]),
            ];
        }

        // Determine eligibility
        $employee->remarks = $remarks ?: $eligibleRemarks;

        if (empty($remarks)) {
            $this->eligibile[] = $employee;
        } else {
            $this->not_eligibile[] = $employee;
        }
    }

    private function getCutoff()
    {
        $dateObj = new \DateTime($this->date);
        $year  = $dateObj->format('Y');
        $month = $dateObj->format('m');

        if ($this->cutoff === 'first_cutoff') {
            $start = "$year-$month-01";
            $end   = "$year-$month-15";
        } elseif ($this->cutoff === 'second_cutoff') {
            $start = "$year-$month-16";
            $end   = $dateObj->format('Y-m-t'); // last day of month
        } else {
            throw new \InvalidArgumentException("Invalid cutoff type: $this->cutoff");
        }

        return [
            'startDate' => $start,
            'endDate'   => $end,
        ];
    }

    public function getHolidays($payload)
    {
        $start_date = $payload['start_date'];
        $end_date = $payload['end_date'];

        $holidays = DB::table('holidays')
            ->where(function ($query) use ($start_date, $end_date) {
                $query
                    // For normal (non-repeating) holidays, use full date range
                    ->where(function ($q) use ($start_date, $end_date) {
                        $q->where('is_repeating', false)
                        ->whereBetween('date', [$start_date, $end_date]);
                    })
                    // For repeating holidays, match only month and day
                    ->orWhere(function ($q) use ($start_date, $end_date) {
                        $startMonthDay = date('m-d', strtotime($start_date));
                        $endMonthDay = date('m-d', strtotime($end_date));

                        $q->where('is_repeating', true)
                        ->whereRaw("DATE_FORMAT(date, '%m-%d') BETWEEN ? AND ?", [$startMonthDay, $endMonthDay]);
                    });
            })
            ->where('is_active', true)
            ->get()
            ->map(function($holiday) use ($start_date) {
                $date = $holiday->is_repeating
                    ? date('Y', strtotime($start_date)) . '-' . date('m-d', strtotime($holiday->date))
                    : $holiday->date;

                   return [
                    'id' => $holiday->id,
                    'title' => ucfirst(str_replace('_', ' ', $holiday->name)),
                    'start' => $date,
                    'allDay' => true,
                    'backgroundColor' => '#008046ff',
                    'borderColor' => '#008046ff',
                    'className' => 'text-white text-center text-shadow-lg d-flex justify-content-center align-items-center h-100 w-100',
                    'extendedProps' => [
                        'id' => $holiday->id,
                        'category' => 'holiday',
                        'type' => $holiday->type,
                        'is_repeating' => (bool) $holiday->is_repeating,
                        'no_work_rate' => $holiday->no_work_rate,
                        'work_rate' => $holiday->work_rate,
                        'overtime_rate' => $holiday->overtime_rate,
                    ],
                ];
            });

        return $holidays;
    }

    public function getSuspensions($payload)
    {
        $start_date = $payload['start_date'];
        $end_date = $payload['end_date'];

        $suspensions = DB::table('suspension_dates')
            ->leftJoin('suspension', 'suspension_dates.suspension_id', '=', 'suspension.id')
            ->select('suspension_dates.*', 'suspension.name', 'suspension.description')
            ->whereBetween('suspension_dates.date', [$start_date, $end_date])
            ->where('suspension_dates.isActive', true)
            ->get()
            ->map(function ($suspension) {
                $title = ucfirst(str_replace('_', ' ', $suspension->name));

                $color = match ($suspension->type) {
                    'whole_day' => '#c0392b', // red for full day
                    'half_day' => '#f39c12',  // orange for half day
                    default => '#7f8c8d',     // gray for unknown type
                };

                $desc = $suspension->description 
                    ? $suspension->description 
                    : ucfirst($suspension->type) . ' suspension';

                return [
                    'id' => $suspension->suspension_id,
                    'title' => $title,
                    'start' => $suspension->date,
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'className' => 'text-white text-center text-shadow-lg d-flex justify-content-center align-items-center h-100 w-100',
                    'extendedProps' => [
                        'id' => $suspension->id,
                        'suspension_id' => $suspension->suspension_id,
                        'category' => 'suspension',
                        'type' => $suspension->type,
                        'shift' => $suspension->shift,
                        'description' => $desc,
                    ],
                ];
            });

        return $suspensions;
    }


}
    