<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\DailyTimeRecordService;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ChiefCornerController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected DailyTimeRecordService $dailyTimeRecordService,
        protected TimelogsServices $timelogsServices,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $scope = $this->chiefScope();
        $monthDate = $this->resolveMonthDate($request);
        $selectedMonth = $monthDate->format('Y-m');

        return view('employee.pages.chief-corner.index', [
            'managedDivisions' => $scope['managedDivisions'],
            'selectedMonth' => $selectedMonth,
            'previousMonth' => $monthDate->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthDate->copy()->addMonth()->format('Y-m'),
            'periodLabel' => $monthDate->format('F Y'),
            'totals' => [
                'divisions' => $scope['managedDivisions']->count(),
                'employees' => $scope['employees']->count(),
                'applications' => $this->applicationCount($scope['employeeNos']),
                'tracked_users' => count($scope['userIds']),
            ],
        ]);
    }

    public function tabData(Request $request, string $tab): JsonResponse
    {
        $scope = $this->chiefScope(
            $this->resolveEmploymentTypeFilter($request)
        );
        $monthDate = $this->resolveMonthDate($request);
        $selectedDate = $this->resolveSelectedDate($request);

        if ($request->boolean('datatable')) {
            return $this->datatablePayload($request, $tab, $scope, $monthDate, $selectedDate);
        }

        return match ($tab) {
            'overview' => response()->json($this->overviewPayload($scope, $monthDate, $selectedDate)),
            'applications' => response()->json($this->applicationsPayload(
                $scope,
                $monthDate,
                $selectedDate,
                $request->string('application_type')->toString()
            )),
            'timelogs' => response()->json($this->timelogsPayload($scope, $monthDate, $selectedDate)),
            'credits' => response()->json($this->creditsPayload($scope, $monthDate, $selectedDate)),
            default => response()->json(['message' => 'Tab not found.'], 404),
        };
    }

    protected function datatablePayload(Request $request, string $tab, array $scope, Carbon $monthDate, ?Carbon $selectedDate): JsonResponse
    {
        return match ($tab) {
            'applications' => response()->json($this->applicationsDataTablePayload(
                $scope,
                $monthDate,
                $selectedDate,
                $request->string('application_type')->toString(),
                $request
            )),
            'timelogs' => response()->json($this->timelogsDataTablePayload($scope, $monthDate, $request)),
            'credits' => response()->json($this->creditsDataTablePayload($scope, $monthDate, $selectedDate, $request)),
            default => response()->json(['message' => 'Table not found.'], 404),
        };
    }

    protected function chiefScope(string $employmentType = 'all'): array
    {
        abort_unless(Auth::user()?->is_division_chief, 403);

        $userId = (int) Auth::id();

        return Cache::remember(
            $this->chiefCacheKey('scope', [
                'user' => $userId,
                'employment_type' => $employmentType,
            ]),
            now()->addMinutes(5),
            function () use ($employmentType) {
                $user = Auth::user();
                $managedDivisions = $user->managedDivisions()
                    ->orderBy('name')
                    ->get(['id', 'code', 'name']);

                $divisionIds = $managedDivisions->pluck('id')->all();
                $employees = $this->divisionEmployees($divisionIds, $employmentType);

                return [
                    'managedDivisions' => $managedDivisions,
                    'employees' => $employees,
                    'employeeNos' => $employees->pluck('employee_no')->filter()->values()->all(),
                    'userIds' => $employees->pluck('user_id')->filter()->values()->all(),
                ];
            }
        );
    }

    protected function resolveEmploymentTypeFilter(Request $request): string
    {
        $employmentType = Str::lower(trim($request->string('employee_type')->toString()));

        return in_array($employmentType, ['all', 'regular', 'cos'], true)
            ? $employmentType
            : 'all';
    }

    protected function resolveMonthDate(Request $request): Carbon
    {
        $selectedMonth = $request->string('month')->toString();
        $selectedMonth = preg_match('/^\d{4}-\d{2}$/', $selectedMonth)
            ? $selectedMonth
            : now()->format('Y-m');

        return Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
    }

    protected function resolveSelectedDate(Request $request): ?Carbon
    {
        $selectedDate = $request->string('selected_date')->toString();

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $selectedDate)->startOfDay();
    }

    protected function divisionEmployees(array $divisionIds, string $employmentType = 'all'): Collection
    {
        if (empty($divisionIds)) {
            return collect();
        }

        $employmentTypeId = match ($employmentType) {
            'regular' => '1',
            'cos' => '2',
            default => null,
        };

        return $this->employeeService
            ->getEmployees('active', null, null, $employmentTypeId)
            ->whereIn('division_id', $divisionIds)
            ->sortBy([
                ['division_name', 'asc'],
                ['lastname', 'asc'],
                ['firstname', 'asc'],
            ])
            ->values()
            ->map(function ($employee) {
                $employee->employee_name = $this->formatEmployeeName(
                    $employee->firstname ?? '',
                    $employee->lastname ?? '',
                    $employee->employee_no ?? ''
                );

                return $employee;
            });
    }

    protected function submittedApplications(array $employeeNos): Collection
    {
        if (empty($employeeNos)) {
            return collect();
        }

        $employeeNameSql = $this->employeeNameSql('ep');

        $leave = DB::table('leave_applications as la')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'la.employee_no')
            ->leftJoin('leaves as l', 'l.id', '=', 'la.leave_id')
            ->leftJoin('leave_dates as ld', 'ld.leave_application_id', '=', 'la.id')
            ->whereIn('la.employee_no', $employeeNos)
            ->selectRaw("
                la.id,
                'Leave' as type,
                la.application_no,
                la.employee_no,
                {$employeeNameSql} as employee_name,
                COALESCE(l.name, la.name) as subject,
                la.status,
                la.created_at,
                CONCAT(MIN(ld.date), CASE WHEN MAX(ld.date) IS NOT NULL AND MAX(ld.date) <> MIN(ld.date) THEN CONCAT(' to ', MAX(ld.date)) ELSE '' END) as schedule_label
            ")
            ->groupBy('la.id', 'la.application_no', 'la.employee_no', 'ep.firstname', 'ep.lastname', 'l.name', 'la.name', 'la.status', 'la.created_at')
            ->get();

        $offset = DB::table('offset_applications as oa')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'oa.employee_no')
            ->leftJoin('offset_dates as od', 'od.offset_application_id', '=', 'oa.id')
            ->whereIn('oa.employee_no', $employeeNos)
            ->selectRaw("
                oa.id,
                'Offset' as type,
                oa.application_no,
                oa.employee_no,
                {$employeeNameSql} as employee_name,
                oa.name as subject,
                oa.status,
                oa.created_at,
                CONCAT(MIN(od.date), CASE WHEN MAX(od.date) IS NOT NULL AND MAX(od.date) <> MIN(od.date) THEN CONCAT(' to ', MAX(od.date)) ELSE '' END) as schedule_label
            ")
            ->groupBy('oa.id', 'oa.application_no', 'oa.employee_no', 'ep.firstname', 'ep.lastname', 'oa.name', 'oa.status', 'oa.created_at')
            ->get();

        $overtime = DB::table('overtime_applications as ot')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ot.employee_no')
            ->whereIn('ot.employee_no', $employeeNos)
            ->selectRaw("
                ot.id,
                'Overtime' as type,
                ot.application_no,
                ot.employee_no,
                {$employeeNameSql} as employee_name,
                'Overtime Request' as subject,
                ot.status,
                ot.created_at,
                DATE_FORMAT(ot.date, '%Y-%m-%d') as schedule_label
            ")
            ->get();

        $passSlip = DB::table('obs_applications as ob')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ob.employee_no')
            ->whereIn('ob.employee_no', $employeeNos)
            ->selectRaw("
                ob.id,
                'Pass Slip' as type,
                ob.application_no,
                ob.employee_no,
                {$employeeNameSql} as employee_name,
                COALESCE(ob.name, ob.reason, 'Pass Slip') as subject,
                ob.status,
                ob.created_at,
                (
                    SELECT CONCAT(
                        MIN(od.date),
                        CASE
                            WHEN MAX(od.date) IS NOT NULL AND MAX(od.date) <> MIN(od.date)
                                THEN CONCAT(' to ', MAX(od.date))
                            ELSE ''
                        END
                    )
                    FROM obs_dates as od
                    WHERE od.obs_application_id = ob.id
                ) as schedule_label
            ")
            ->get();

        return $leave
            ->concat($offset)
            ->concat($overtime)
            ->concat($passSlip)
            ->sortByDesc('created_at')
            ->values();
    }

    protected function cachedSubmittedApplications(array $employeeNos, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        return Cache::remember(
            $this->chiefCacheKey('applications', [
                'employees' => md5(json_encode($employeeNos)),
                'month' => $monthDate->format('Y-m'),
                'date' => $selectedDate?->toDateString(),
            ]),
            now()->addMinutes(5),
            fn () => $this->filterApplicationsByPeriod(
                $this->submittedApplications($employeeNos),
                $monthDate,
                $selectedDate
            )
        );
    }

    protected function cachedTimelogInsights(array $scope, Carbon $monthDate): array
    {
        return Cache::remember(
            $this->chiefCacheKey('timelog-insights', [
                'month' => $monthDate->format('Y-m'),
                'users' => md5(json_encode($scope['userIds'])),
            ]),
            now()->addMinutes(10),
            fn () => $this->timelogInsights($scope['employees'], $monthDate)
        );
    }

    protected function cachedTimelogDailyRows(array $scope, Carbon $monthDate): Collection
    {
        return Cache::remember(
            $this->chiefCacheKey('timelog-daily-rows', [
                'month' => $monthDate->format('Y-m'),
                'users' => md5(json_encode($scope['userIds'])),
            ]),
            now()->addMinutes(10),
            fn () => $this->timelogDailyRows($scope['employees'], $monthDate)
        );
    }

    protected function timelogInsights(Collection $employees, Carbon $monthDate): array
    {
        $startDate = $monthDate->copy()->startOfMonth()->toDateString();
        $endDate = $monthDate->copy()->endOfMonth()->toDateString();
        $shiftCache = [];

        $summaries = $employees
            ->filter(fn ($employee) => !empty($employee->user_id))
            ->map(function ($employee) use ($startDate, $endDate, &$shiftCache) {
                if (empty($employee->shift_id) || empty($employee->work_schedule_id)) {
                    return (object) [
                        'employee_no' => $employee->employee_no,
                        'employee_name' => $employee->employee_name,
                        'firstname' => $employee->firstname ?? null,
                        'lastname' => $employee->lastname ?? null,
                        'division_name' => $employee->division_name,
                        'unit_name' => $employee->unit_name,
                        'position_name' => $employee->position_name,
                        'days_with_logs' => 0,
                        'worked_minutes' => 0,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,
                        'early_minutes' => 0,
                        'overtime_minutes' => 0,
                        'leave_days' => 0,
                        'offset_days' => 0,
                        'so_days' => 0,
                        'lto_days' => 0,
                        'ontime_days' => 0,
                        'last_log_date' => null,
                    ];
                }

                try {
                    $dtr = $this->dailyTimeRecordService->getDTR([
                        'user_id' => $employee->user_id,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);
                    $rawLogsByDate = collect(
                        $this->timelogsServices->getTimeLogsWithPeriod($employee->user_id, $startDate, $endDate)
                    )->keyBy('date');
                } catch (Throwable) {
                    return (object) [
                        'employee_no' => $employee->employee_no,
                        'employee_name' => $employee->employee_name,
                        'firstname' => $employee->firstname ?? null,
                        'lastname' => $employee->lastname ?? null,
                        'division_name' => $employee->division_name,
                        'unit_name' => $employee->unit_name,
                        'position_name' => $employee->position_name,
                        'days_with_logs' => 0,
                        'worked_minutes' => 0,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,
                        'early_minutes' => 0,
                        'overtime_minutes' => 0,
                        'leave_days' => 0,
                        'offset_days' => 0,
                        'so_days' => 0,
                        'lto_days' => 0,
                        'ontime_days' => 0,
                        'last_log_date' => null,
                        'late_breakdown' => [],
                        'undertime_breakdown' => [],
                        'leave_breakdown' => [],
                        'offset_breakdown' => [],
                        'so_breakdown' => [],
                        'lto_breakdown' => [],
                    ];
                }

                $rows = collect($dtr['computedData'] ?? []);
                $timelogRows = $rows->filter(fn ($row) => !empty($row['time_in']) || !empty($row['time_out']));

                $lateMinutes = 0;
                $earlyMinutes = 0;
                $onTimeDays = 0;
                $workedMinutes = 0;
                $lateBreakdown = [];
                $undertimeBreakdown = [];
                $leaveBreakdown = [];
                $offsetBreakdown = [];
                $soBreakdown = [];
                $ltoBreakdown = [];

                foreach ($rows as $row) {
                    $rowDate = (string) ($row['date'] ?? '');

                    if (!empty($row['time_in']) && !empty($row['shift_id'])) {
                        if (!isset($shiftCache[$row['shift_id']])) {
                            $shiftCache[$row['shift_id']] = DB::table('shifts')->find($row['shift_id']);
                        }

                        $shift = $shiftCache[$row['shift_id']];
                        if ($shift?->start_time) {
                            $shiftStart = Carbon::parse($row['date'] . ' ' . $shift->start_time);
                            $actualIn = Carbon::parse($row['date'] . ' ' . $row['time_in']);

                            if ($actualIn->lt($shiftStart)) {
                                $earlyMinutes += $actualIn->diffInMinutes($shiftStart);
                            }
                        }
                    }

                    $rawLog = $rawLogsByDate->get($rowDate);
                    if ($rawLog && !empty($rawLog['shift_id'])) {
                        $tardinessData = $this->timelogsServices->computeTardinessAndUndertime([
                            'date' => $rowDate,
                            'shift_id' => $rawLog['shift_id'],
                            'time_in' => $rawLog['time_in'] ?? null,
                            'time_out' => $rawLog['time_out'] ?? null,
                            'break_out' => $rawLog['break_out'] ?? null,
                            'break_in' => $rawLog['break_in'] ?? null,
                        ]);

                        if (($tardinessData['total_tardiness'] ?? 0) > 0) {
                            $lateBreakdown[] = $this->timelogDurationBreakdownItem(
                                $rowDate,
                                (int) $tardinessData['total_tardiness'],
                                [
                                    'Time in ' . $this->formatTimelogTime($rawLog['time_in'] ?? null),
                                    'Expected start ' . $this->expectedShiftStartLabel($rawLog['shift_id'], $shiftCache),
                                ]
                            );
                        }

                        if (($tardinessData['total_undertime'] ?? 0) > 0) {
                            $undertimeBreakdown[] = $this->timelogDurationBreakdownItem(
                                $rowDate,
                                (int) $tardinessData['total_undertime'],
                                [
                                    'Time out ' . $this->formatTimelogTime($rawLog['time_out'] ?? null),
                                    'Expected end ' . $this->expectedShiftEndLabel($rawLog['shift_id'], $shiftCache),
                                ]
                            );
                        }
                    }

                    $workedMinutes += max((int) ($row['total_time_work'] ?? 0), 0);

                    $remarks = $this->normalizeTimelogRemarks($row['remarks'] ?? []);
                    foreach ($remarks as $remark) {
                        $remarkLower = Str::lower($remark);

                        if (str_contains($remarkLower, 'leave')) {
                            $leaveBreakdown[$rowDate] = $this->timelogDayBreakdownItem($rowDate, $remark);
                        }

                        if (str_contains($remarkLower, 'offset')) {
                            $offsetBreakdown[$rowDate] = $this->timelogDayBreakdownItem($rowDate, $remark);
                        }

                        if (str_contains($remarkLower, 'special order')) {
                            $soBreakdown[$rowDate] = $this->timelogDayBreakdownItem($rowDate, $remark);
                        }

                        if (preg_match('/\blto\b|local travel order/', $remarkLower)) {
                            $ltoBreakdown[$rowDate] = $this->timelogDayBreakdownItem($rowDate, $remark);
                        }
                    }

                    if (!empty($row['time_in']) && !empty($row['time_out']) && empty($row['late_undertime'])) {
                        $onTimeDays++;
                    }
                }

                $summaryMap = collect($dtr['summary'] ?? [])->keyBy('label');
                $payrollValues = collect($dtr['payroll_value'] ?? []);
                $lateUndertime = (int) data_get($summaryMap->get('Late / Undertime'), 'actual_value', 0);

                foreach ($rows as $row) {
                    if (!empty($row['shift_id']) && (!isset($shiftCache[$row['shift_id']]))) {
                        $shiftCache[$row['shift_id']] = DB::table('shifts')->find($row['shift_id']);
                    }

                    $shift = $row['shift_id'] ? ($shiftCache[$row['shift_id']] ?? null) : null;

                    if ($shift?->start_time && !empty($row['time_in'])) {
                        $shiftStart = Carbon::parse($row['date'] . ' ' . $shift->start_time);
                        $actualIn = Carbon::parse($row['date'] . ' ' . $row['time_in']);
                        if ($actualIn->gt($shiftStart)) {
                            $lateMinutes += $shiftStart->diffInMinutes($actualIn);
                        }
                    }
                }

                $undertimeMinutes = max($lateUndertime - $lateMinutes, 0);
                $latestRow = $timelogRows->sortByDesc('date')->first();

                return (object) [
                    'employee_no' => $employee->employee_no,
                    'employee_name' => $employee->employee_name,
                    'firstname' => $employee->firstname ?? null,
                    'lastname' => $employee->lastname ?? null,
                    'division_name' => $employee->division_name,
                    'unit_name' => $employee->unit_name,
                    'position_name' => $employee->position_name,
                    'days_with_logs' => $timelogRows->count(),
                    'worked_minutes' => $workedMinutes,
                    'late_minutes' => $lateMinutes,
                    'undertime_minutes' => $undertimeMinutes,
                    'early_minutes' => $earlyMinutes,
                    'overtime_minutes' => (int) $payrollValues->get('overtime', 0),
                    'leave_days' => (float) $payrollValues->get('leaves', 0),
                    'offset_days' => (float) $payrollValues->get('offset', 0),
                    'so_days' => (float) $payrollValues->get('so', 0),
                    'lto_days' => (float) $payrollValues->get('lto', 0),
                    'ontime_days' => $onTimeDays,
                    'last_log_date' => $latestRow['date'] ?? null,
                    'late_breakdown' => array_values($lateBreakdown),
                    'undertime_breakdown' => array_values($undertimeBreakdown),
                    'leave_breakdown' => array_values($leaveBreakdown),
                    'offset_breakdown' => array_values($offsetBreakdown),
                    'so_breakdown' => array_values($soBreakdown),
                    'lto_breakdown' => array_values($ltoBreakdown),
                ];
            })
            ->values();

        return [
            $summaries,
            [
                'lates' => $summaries->filter(fn ($row) => $row->late_minutes > 0)->sortByDesc('late_minutes')->take(10)->values(),
                'undertime' => $summaries->filter(fn ($row) => $row->undertime_minutes > 0)->sortByDesc('undertime_minutes')->take(10)->values(),
                'leave' => $summaries->filter(fn ($row) => $row->leave_days > 0)->sortByDesc('leave_days')->take(10)->values(),
                'offsets' => $summaries->filter(fn ($row) => $row->offset_days > 0)->sortByDesc('offset_days')->take(10)->values(),
                'so' => $summaries->filter(fn ($row) => $row->so_days > 0)->sortByDesc('so_days')->take(10)->values(),
                'lto' => $summaries->filter(fn ($row) => $row->lto_days > 0)->sortByDesc('lto_days')->take(10)->values(),
            ],
        ];
    }

    protected function leaveCredits(array $employeeNos, ?Carbon $asOfDate = null): Collection
    {
        if (empty($employeeNos)) {
            return collect();
        }

        $latestLeaveCredits = DB::table('leave_credits as lc1')
            ->selectRaw('MAX(lc1.id) as id')
            ->whereIn('lc1.employee_no', $employeeNos)
            ->groupBy('lc1.employee_no', 'lc1.leave_id');

        $employeeNameSql = $this->employeeNameSql('ep');

        return DB::table('leave_credits as lc')
            ->joinSub($latestLeaveCredits, 'latest_leave_credits', function ($join) {
                $join->on('lc.id', '=', 'latest_leave_credits.id');
            })
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'lc.employee_no')
            ->leftJoin('leaves as l', 'l.id', '=', 'lc.leave_id')
            ->selectRaw("
                lc.employee_no,
                {$employeeNameSql} as employee_name,
                lc.leave_id,
                l.name as leave_name,
                lc.balance,
                lc.as_of,
                lc.remarks
            ")
            ->orderBy('employee_name')
            ->orderBy('leave_name')
            ->get();
    }

    protected function leaveCreditTypes(array $employeeNos, ?Carbon $asOfDate = null): Collection
    {
        if (empty($employeeNos)) {
            return collect();
        }

        $creditTypes = DB::table('leave_credits as lc')
            ->leftJoin('leaves as l', 'l.id', '=', 'lc.leave_id')
            ->whereIn('lc.employee_no', $employeeNos)
            ->select('lc.leave_id', 'l.name as leave_name')
            ->distinct()
            ->orderBy('l.name')
            ->get();

        if ($creditTypes->isNotEmpty()) {
            return $creditTypes;
        }

        return DB::table('leaves')
            ->when(
                DB::getSchemaBuilder()->hasColumn('leaves', 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->select('id as leave_id', 'name as leave_name')
            ->whereNotNull('name')
            ->orderBy('name')
            ->get();
    }

    protected function offsetCredits(array $employeeNos, ?Carbon $asOfDate = null): Collection
    {
        if (empty($employeeNos)) {
            return collect();
        }

        $latestOffsetCredits = DB::table('offset_credits as oc1')
            ->selectRaw('MAX(oc1.id) as id')
            ->whereIn('oc1.employee_no', $employeeNos)
            ->groupBy('oc1.employee_no');

        $employeeNameSql = $this->employeeNameSql('ep');

        return DB::table('offset_credits as oc')
            ->joinSub($latestOffsetCredits, 'latest_offset_credits', function ($join) {
                $join->on('oc.id', '=', 'latest_offset_credits.id');
            })
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'oc.employee_no')
            ->selectRaw("
                oc.employee_no,
                {$employeeNameSql} as employee_name,
                oc.previous,
                oc.earned,
                oc.deducted,
                oc.balance,
                oc.as_of,
                oc.remarks
            ")
            ->orderBy('employee_name')
            ->get();
    }

    protected function cachedLeaveCredits(array $employeeNos, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        return Cache::remember(
            $this->chiefCacheKey('leave-credits', [
                'employees' => md5(json_encode($employeeNos)),
            ]),
            now()->addMinutes(10),
            fn () => $this->leaveCredits($employeeNos)
        );
    }

    protected function cachedOffsetCredits(array $employeeNos, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        return Cache::remember(
            $this->chiefCacheKey('offset-credits', [
                'employees' => md5(json_encode($employeeNos)),
            ]),
            now()->addMinutes(10),
            fn () => $this->offsetCredits($employeeNos)
        );
    }

    protected function overviewPayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate = null): array
    {
        $applications = $this->cachedSubmittedApplications($scope['employeeNos'], $monthDate, $selectedDate)->take(12)->values();
        [$timelogSummaries, $timelogStats] = $this->cachedTimelogInsights($scope, $monthDate);

        return [
            'applications' => $applications->map(fn ($application) => $this->formatApplicationRow($application))->values(),
            'highlight_cards' => [
                'top_late' => $this->formatTopTimelogCards(
                    $timelogStats['lates']->take(3),
                    'late_minutes',
                    'duration'
                ),
                'top_ontime' => $this->formatTopTimelogCards(
                    $timelogSummaries->filter(fn ($row) => $row->ontime_days > 0)->sortByDesc('ontime_days')->take(3),
                    'ontime_days',
                    'days'
                ),
                'period_label' => strtoupper($monthDate->format('F Y')),
            ],
        ];
    }

    protected function applicationsPayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate = null, string $applicationType = ''): array
    {
        $applications = $this->cachedSubmittedApplications($scope['employeeNos'], $monthDate, $selectedDate);

        if ($applicationType !== '') {
            $applications = $applications
                ->filter(fn ($application) => $application->type === $applicationType)
                ->values();
        }

        return [
            'application_type' => $applicationType,
            'applications_count' => $applications->count(),
        ];
    }

    protected function timelogsPayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate = null): array
    {
        return [
            'selected_month' => $monthDate->format('Y-m'),
            'selected_date' => $selectedDate?->toDateString(),
            'previous_month' => $monthDate->copy()->subMonth()->format('Y-m'),
            'next_month' => $monthDate->copy()->addMonth()->format('Y-m'),
            'period_label' => strtoupper($monthDate->format('F Y')),
            'stats' => [],
            'summary_count' => 0,
            'daily_count' => 0,
        ];
    }

    protected function timelogDailyRows(Collection $employees, Carbon $monthDate): Collection
    {
        $startDate = $monthDate->copy()->startOfMonth()->toDateString();
        $endDate = $monthDate->copy()->endOfMonth()->toDateString();
        $today = now()->toDateString();
        $isCurrentMonth = $monthDate->isSameMonth(now());

        return $employees
            ->filter(fn ($employee) => !empty($employee->user_id))
            ->flatMap(function ($employee) use ($startDate, $endDate, $today, $isCurrentMonth) {
                if (empty($employee->shift_id) || empty($employee->work_schedule_id)) {
                    return collect();
                }

                try {
                    $dtr = $this->dailyTimeRecordService->getDTR([
                        'user_id' => $employee->user_id,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);
                } catch (Throwable) {
                    return collect();
                }

                return collect($dtr['computedData'] ?? [])
                    ->filter(function ($row) use ($today, $isCurrentMonth) {
                        if ($isCurrentMonth && !empty($row['date']) && Carbon::parse($row['date'])->toDateString() > $today) {
                            return false;
                        }

                        $remarks = $this->normalizeTimelogRemarks($row['remarks'] ?? []);

                        return !empty($row['time_in'])
                            || !empty($row['time_out'])
                            || !empty($row['overtime'])
                            || !empty($row['break'])
                            || (int) ($row['late_undertime'] ?? 0) > 0
                            || (int) ($row['total_time_work'] ?? 0) > 0
                            || $remarks->isNotEmpty();
                    })
                    ->map(function ($row) use ($employee) {
                        $remarks = $this->normalizeTimelogRemarks($row['remarks'] ?? []);
                        $date = Carbon::parse($row['date']);
                        $breakMinutes = (int) ($row['break'] ?? 0);
                        $overtimeMinutes = (int) ($row['overtime'] ?? 0);
                        $lateUndertimeMinutes = (int) ($row['late_undertime'] ?? 0);
                        $workMinutes = (int) ($row['total_time_work'] ?? 0);
                        [$breakOut, $breakIn] = $this->splitTimelogRange($row['break'] ?? null);

                        return [
                            'date' => $date->format('M d, Y'),
                            'date_key' => $date->toDateString(),
                            'date_order' => $date->timestamp,
                            'day_name' => $date->format('D'),
                            'employee' => $employee->employee_name ?: $employee->employee_no,
                            'employee_order' => $this->employeeSortValue(
                                $employee->lastname ?? null,
                                $employee->firstname ?? null,
                                $employee->employee_name ?: $employee->employee_no
                            ),
                            'unit' => $employee->unit_name ?: '-',
                            'unit_order' => mb_strtolower($employee->unit_name ?: '-'),
                            'position' => $employee->position_name ?: 'No position',
                            'time_in' => $row['time_in'] ? Carbon::parse($row['time_in'])->format('h:i A') : '--',
                            'time_in_order' => $this->timeOrderValue($row['time_in'] ?? null),
                            'break_out' => $breakOut,
                            'break_out_order' => $this->timeOrderValue($breakOut),
                            'break_in' => $breakIn,
                            'break_in_order' => $this->timeOrderValue($breakIn),
                            'time_out' => $row['time_out'] ? Carbon::parse($row['time_out'])->format('h:i A') : '--',
                            'time_out_order' => $this->timeOrderValue($row['time_out'] ?? null),
                            'ot' => $row['overtime'] ?: '-- : -- to -- : --',
                            'overtime_order' => $overtimeMinutes,
                            'late_undertime' => $this->minutesToHoursLabel($lateUndertimeMinutes),
                            'late_undertime_order' => $lateUndertimeMinutes,
                            'worked_hours' => $this->workedDurationLabel($workMinutes),
                            'worked_hours_order' => $workMinutes,
                            'remarks' => $remarks->isNotEmpty() ? $remarks->join(', ') : 'Logged',
                        ];
                    });
            })
            ->sortByDesc('date_order')
            ->values();
    }

    protected function normalizeTimelogRemarks($remarks): Collection
    {
        return collect(Arr::wrap($remarks))
            ->flatMap(function ($remark) {
                if (is_array($remark)) {
                    if (array_key_exists('text', $remark)) {
                        return [$remark['text']];
                    }

                    return collect($remark)
                        ->flatten(1)
                        ->filter(fn ($value) => !is_array($value))
                        ->values()
                        ->all();
                }

                return [$remark];
            })
            ->filter(fn ($remark) => filled($remark))
            ->map(function ($remark) {
                $remark = str_replace('_', ' ', (string) $remark);

                return ucwords(trim($remark));
            })
            ->filter(fn ($remark) => $remark !== '')
            ->values();
    }

    protected function formatTimelogStatRows(Collection $rows, string $field, string $format): Collection
    {
        return $rows->map(function ($row) use ($field, $format) {
            $value = $row->{$field} ?? 0;
            $breakdown = $this->timelogStatBreakdownPayload($row, $field, $format);

            return [
                'employee' => $row->employee_name ?: $row->employee_no,
                'employee_order' => $this->employeeSortValue(
                    $row->lastname ?? null,
                    $row->firstname ?? null,
                    $row->employee_name ?: $row->employee_no
                ),
                'unit' => $row->unit_name ?: '-',
                'position' => $row->position_name ?: 'No position',
                'value' => $format === 'duration'
                    ? $this->minutesToHoursLabel((int) $value)
                    : $this->formatDayCount((float) $value),
                'value_order' => $value,
                'breakdown_id' => $breakdown['id'],
                'breakdown' => $breakdown,
            ];
        })->values();
    }

    protected function formatDayCount(float $value): string
    {
        $formatted = fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

        return $formatted . ' day' . ((float) $formatted === 1.0 ? '' : 's');
    }

    protected function minutesToHoursLabel(int $minutes): string
    {
        $totalMinutes = max($minutes, 0);
        $hours = intdiv($totalMinutes, 60);
        $remainingMinutes = $totalMinutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return sprintf(
                '%d %s %d %s',
                $hours,
                $hours === 1 ? 'hr' : 'hrs',
                $remainingMinutes,
                $remainingMinutes === 1 ? 'min' : 'mins'
            );
        }

        if ($hours > 0) {
            return sprintf(
                '%d %s',
                $hours,
                $hours === 1 ? 'hr' : 'hrs'
            );
        }

        return sprintf(
            '%d %s',
            $remainingMinutes,
            $remainingMinutes === 1 ? 'min' : 'mins'
        );
    }

    protected function workedDurationLabel(int $minutes): string
    {
        $totalMinutes = max($minutes, 0);

        if ($totalMinutes < 1440) {
            return $this->minutesToHoursLabel($totalMinutes);
        }

        $days = intdiv($totalMinutes, 1440);
        $remainingMinutes = $totalMinutes % 1440;
        $parts = [
            sprintf('%d %s', $days, $days === 1 ? 'day' : 'days'),
        ];

        if ($remainingMinutes > 0) {
            $parts[] = $this->minutesToHoursLabel($remainingMinutes);
        }

        return implode(' ', $parts);
    }

    protected function timeOrderValue(?string $time): int
    {
        $normalizedTime = trim((string) $time);

        if ($normalizedTime === '' || str_replace(['-', ':', ' '], '', $normalizedTime) === '') {
            return -1;
        }

        $parsedTime = Carbon::parse($normalizedTime);

        return ((int) $parsedTime->format('H') * 60) + (int) $parsedTime->format('i');
    }

    protected function splitTimelogRange(?string $range): array
    {
        if (blank($range)) {
            return ['--', '--'];
        }

        [$from, $to] = array_pad(explode(' to ', (string) $range, 2), 2, '--');

        return [trim($from) !== '' ? trim($from) : '--', trim($to) !== '' ? trim($to) : '--'];
    }

    protected function creditsPayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate = null): array
    {
        $leaveCreditColumns = $this->leaveCreditColumns($scope['employeeNos'], $monthDate, $selectedDate);
        $leaveCredits = $this->cachedLeaveCredits($scope['employeeNos'], $monthDate, $selectedDate);
        $offsetCredits = $this->cachedOffsetCredits($scope['employeeNos'], $monthDate, $selectedDate);

        return [
            'leave_credit_columns' => $leaveCreditColumns->map(fn ($column) => [
                'key' => $column['key'],
                'label' => $column['label'],
            ])->values()->all(),
            'leave_credits_count' => $leaveCredits->pluck('employee_no')->unique()->count(),
            'offset_credits_count' => $offsetCredits->pluck('employee_no')->unique()->count(),
        ];
    }

    protected function applicationsDataTablePayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate, string $applicationType, Request $request): array
    {
        $applications = $this->cachedSubmittedApplications($scope['employeeNos'], $monthDate, $selectedDate);

        if ($applicationType !== '') {
            $applications = $applications->where('type', $applicationType)->values();
        }

        return $this->datatableCollectionResponse(
            $applications->map(fn ($application) => $this->formatApplicationRow($application)),
            $request
        );
    }

    protected function timelogsDataTablePayload(array $scope, Carbon $monthDate, Request $request): array
    {
        $table = $request->string('table')->toString();

        if ($monthDate->copy()->startOfMonth()->gt(now()->copy()->startOfMonth())) {
            return $this->datatableCollectionResponse(collect(), $request);
        }

        [$timelogSummaries, $timelogStats] = $this->cachedTimelogInsights($scope, $monthDate);
        $dailyRows = $table === 'timelogDaily'
            ? $this->cachedTimelogDailyRows($scope, $monthDate)
            : collect();
        $selectedDate = $request->string('selected_date')->toString();
        if ($table === 'timelogDaily' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $dailyRows = $dailyRows
                ->filter(fn ($row) => ($row['date_key'] ?? null) === $selectedDate)
                ->values();
        }
        $stat = $request->string('stat')->toString();

        return match ($table) {
            'timelogStats' => $this->datatableCollectionResponse(
                $this->timelogStatTableRows($timelogStats, $stat),
                $request
            ),
            'timelogSummary' => $this->datatableCollectionResponse(
                $timelogSummaries->map(fn ($summary) => $this->formatTimelogSummaryRow($summary)),
                $request
            ),
            'timelogDaily' => $this->datatableCollectionResponse($dailyRows, $request),
            default => $this->datatableCollectionResponse(collect(), $request),
        };
    }

    protected function timelogStatTableRows(array $timelogStats, string $stat): Collection
    {
        $fieldMap = [
            'lates' => ['field' => 'late_minutes', 'format' => 'duration'],
            'undertime' => ['field' => 'undertime_minutes', 'format' => 'duration'],
            'leave' => ['field' => 'leave_days', 'format' => 'days'],
            'offsets' => ['field' => 'offset_days', 'format' => 'days'],
            'so' => ['field' => 'so_days', 'format' => 'days'],
            'lto' => ['field' => 'lto_days', 'format' => 'days'],
        ];

        $selectedStat = $fieldMap[$stat] ?? $fieldMap['lates'];
        $rows = collect($timelogStats[$stat] ?? $timelogStats['lates'] ?? []);

        return $this->formatTimelogStatRows(
            $rows,
            $selectedStat['field'],
            $selectedStat['format']
        );
    }

    protected function creditsDataTablePayload(array $scope, Carbon $monthDate, ?Carbon $selectedDate, Request $request): array
    {
        $table = $request->string('table')->toString();

        return match ($table) {
            'leaveCredits' => $this->datatableCollectionResponse(
                $this->leaveCreditMatrixRows($scope, $monthDate, $selectedDate),
                $request
            ),
            'offsetCredits' => $this->datatableCollectionResponse(
                $this->cachedOffsetCredits($scope['employeeNos'], $monthDate, $selectedDate)->map(fn ($credit) => $this->formatOffsetCreditRow($credit)),
                $request
            ),
            default => $this->datatableCollectionResponse(collect(), $request),
        };
    }

    protected function datatableCollectionResponse(Collection $rows, Request $request): array
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);
        $search = trim((string) $request->input('search.value', ''));

        $rows = $rows->values();
        $recordsTotal = $rows->count();

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $rows = $rows->filter(function ($row) use ($searchLower) {
                $haystack = collect(Arr::flatten((array) $row))
                    ->map(fn ($value) => mb_strtolower((string) $value))
                    ->join(' ');

                return str_contains($haystack, $searchLower);
            })->values();
        }

        $recordsFiltered = $rows->count();
        $orderColumnIndex = $request->input('order.0.column');
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderColumn = is_numeric($orderColumnIndex)
            ? ($request->input('columns.' . $orderColumnIndex . '.name')
                ?: $request->input('columns.' . $orderColumnIndex . '.data'))
            : null;

        if (is_string($orderColumn) && $orderColumn !== '') {
            $rows = $orderDirection === 'desc'
                ? $rows->sortByDesc(fn ($row) => data_get($row, $orderColumn))->values()
                : $rows->sortBy(fn ($row) => data_get($row, $orderColumn))->values();
        }

        $pageRows = $rows->slice($start, $length)->values();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pageRows->all(),
        ];
    }

    protected function applicationCount(array $employeeNos): int
    {
        if (empty($employeeNos)) {
            return 0;
        }

        return DB::table('leave_applications')->whereIn('employee_no', $employeeNos)->count()
            + DB::table('offset_applications')->whereIn('employee_no', $employeeNos)->count()
            + DB::table('overtime_applications')->whereIn('employee_no', $employeeNos)->count()
            + DB::table('obs_applications')->whereIn('employee_no', $employeeNos)->count();
    }

    protected function filterApplicationsByPeriod(Collection $applications, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        return $applications
            ->filter(function ($application) use ($monthDate, $selectedDate) {
                $createdAt = Carbon::parse($application->created_at);

                if ($selectedDate) {
                    return $createdAt->isSameDay($selectedDate);
                }

                return $createdAt->betweenIncluded(
                    $monthDate->copy()->startOfMonth(),
                    $monthDate->copy()->endOfMonth()
                );
            })
            ->values();
    }

    protected function formatApplicationRow(object $application): array
    {
        $createdAt = Carbon::parse($application->created_at);

        return [
            'submitted' => $createdAt->format('M d, Y h:i A'),
            'submitted_order' => $createdAt->timestamp,
            'type' => $application->type,
            'application_no' => strtoupper($application->application_no),
            'employee' => trim($application->employee_name) ?: $application->employee_no,
            'subject' => $application->subject ?: '-',
            'schedule' => $application->schedule_label ?: '-',
            'status' => strtoupper($application->status),
            'status_class' => match (strtolower($application->status)) {
                'approved' => 'success',
                'rejected', 'cancelled' => 'danger',
                default => 'warning',
            },
        ];
    }

    protected function formatTimelogSummaryRow(object $summary): array
    {
        return [
            'employee' => $summary->employee_name ?: $summary->employee_no,
            'employee_order' => $this->employeeSortValue(
                $summary->lastname ?? null,
                $summary->firstname ?? null,
                $summary->employee_name ?: $summary->employee_no
            ),
            'position' => $summary->position_name ?: 'No position',
            'unit' => $summary->unit_name ?: '-',
            'unit_order' => mb_strtolower($summary->unit_name ?: '-'),
            'worked_hours' => $this->workedDurationLabel((int) ($summary->worked_minutes ?? 0)),
            'worked_hours_order' => (int) ($summary->worked_minutes ?? 0),
            'late' => $this->minutesToHoursLabel((int) $summary->late_minutes),
            'late_order' => (int) $summary->late_minutes,
            'undertime' => $this->minutesToHoursLabel((int) $summary->undertime_minutes),
            'undertime_order' => (int) $summary->undertime_minutes,
            'overtime' => $this->minutesToHoursLabel((int) $summary->overtime_minutes),
            'overtime_order' => (int) $summary->overtime_minutes,
            'leave' => $this->formatDayCount((float) $summary->leave_days),
            'leave_order' => (float) $summary->leave_days,
            'offset' => $this->formatDayCount((float) $summary->offset_days),
            'offset_order' => (float) $summary->offset_days,
            'so' => $this->formatDayCount((float) $summary->so_days),
            'so_order' => (float) $summary->so_days,
            'lto' => $this->formatDayCount((float) $summary->lto_days),
            'lto_order' => (float) $summary->lto_days,
        ];
    }

    protected function leaveCreditColumns(array $employeeNos, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        $fixedColumns = collect([
            ['name' => 'Vacation Leave', 'key' => 'vacation_leave', 'label' => 'VACATION LEAVE (VL)'],
            ['name' => 'Sick Leave', 'key' => 'sick_leave', 'label' => 'SICK LEAVE (SL)'],
            ['name' => 'Wellness Leave', 'key' => 'wellness_leave', 'label' => 'WELLNESS LEAVE (WL)'],
        ]);

        $leaveIdsByName = DB::table('leaves')
            ->whereIn('name', $fixedColumns->pluck('name')->all())
            ->pluck('id', 'name');

        return $fixedColumns
            ->map(function ($column) use ($leaveIdsByName) {
                return [
                    'leave_id' => (int) ($leaveIdsByName[$column['name']] ?? 0),
                    'leave_name' => $column['name'],
                    'key' => $column['key'],
                    'label' => $column['label'],
                ];
            })
            ->values();
    }

    protected function leaveCreditMatrixRows(array $scope, Carbon $monthDate, ?Carbon $selectedDate = null): Collection
    {
        $leaveCreditColumns = $this->leaveCreditColumns($scope['employeeNos'], $monthDate, $selectedDate);
        $leaveCreditsByEmployee = $this->cachedLeaveCredits($scope['employeeNos'], $monthDate, $selectedDate)
            ->groupBy('employee_no')
            ->map(function ($credits) {
                return [
                    'by_id' => $credits->keyBy('leave_id'),
                    'by_name' => $credits->keyBy(fn ($credit) => Str::lower(trim((string) ($credit->leave_name ?? '')))),
                ];
            });
        $offsetCreditsByEmployee = $this->cachedOffsetCredits($scope['employeeNos'], $monthDate, $selectedDate)->keyBy('employee_no');

        return $scope['employees']
            ->map(function ($employee) use ($leaveCreditColumns, $leaveCreditsByEmployee, $offsetCreditsByEmployee) {
                $employeeNo = (string) $employee->employee_no;
                $employeeName = trim((string) ($employee->employee_name ?: $employeeNo));
                $employeeCredits = $leaveCreditsByEmployee->get($employeeNo, [
                    'by_id' => collect(),
                    'by_name' => collect(),
                ]);
                $offsetCredit = $offsetCreditsByEmployee->get($employeeNo);

                $row = [
                    'employee_no' => $employeeNo,
                    'employee_no_order' => $employeeNo,
                    'employee' => $employeeName,
                    'employee_lastname_order' => mb_strtolower(trim((string) ($employee->lastname ?? ''))),
                    'employee_firstname_order' => mb_strtolower(trim((string) ($employee->firstname ?? ''))),
                    'employee_order' => mb_strtolower($employeeName),
                ];

                foreach ($leaveCreditColumns as $column) {
                    $credit = null;

                    if (($column['leave_id'] ?? 0) > 0) {
                        $credit = data_get($employeeCredits, 'by_id')->get($column['leave_id']);
                    }

                    if (!$credit) {
                        $credit = data_get($employeeCredits, 'by_name')->get(
                            Str::lower(trim((string) ($column['leave_name'] ?? '')))
                        );
                    }

                    $balance = (float) data_get($credit, 'balance', 0);

                    $row[$column['key']] = $this->formatLeaveCreditBalance($balance);
                    $row[$column['key'] . '_order'] = $balance;
                }

                $offsetBalance = (float) data_get($offsetCredit, 'balance', 0);
                $row['offset'] = $this->formatOffsetCreditBalance($offsetBalance);
                $row['offset_order'] = $offsetBalance;

                return $row;
            })
            ->sortBy([
                ['employee_lastname_order', 'asc'],
                ['employee_firstname_order', 'asc'],
                ['employee_no_order', 'asc'],
            ])
            ->values();
    }

    protected function leaveCreditColumnKey(string $leaveName, int $leaveId): string
    {
        $slug = Str::slug($leaveName, '_');

        return $slug !== '' ? $slug : 'leave_' . $leaveId;
    }

    protected function leaveCreditColumnLabel(string $leaveName): string
    {
        return strtoupper($leaveName) . ' (' . $this->leaveCreditAbbreviation($leaveName) . ')';
    }

    protected function leaveCreditAbbreviation(string $leaveName): string
    {
        $words = collect(preg_split('/[\s\/\-\(\)]+/', strtoupper($leaveName)) ?: [])
            ->filter(fn ($word) => $word !== '' && !in_array($word, ['AND', 'OF', 'FOR', 'THE'], true))
            ->values();

        if ($words->isEmpty()) {
            return 'LV';
        }

        return $words
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->join('');
    }

    protected function formatLeaveCreditBalance(float $balance): string
    {
        if (abs($balance) < 0.0005) {
            return '0';
        }

        return number_format($balance, 3, '.', '');
    }

    protected function formatOffsetCreditBalance(float $balance): string
    {
        return number_format($balance, 2, '.', '');
    }

    protected function formatOffsetCreditRow(object $credit): array
    {
        return [
            'employee' => trim($credit->employee_name) ?: $credit->employee_no,
            'employee_order' => mb_strtolower(trim($credit->employee_name) ?: $credit->employee_no),
            'previous' => $credit->previous,
            'previous_order' => (float) $credit->previous,
            'earned' => $credit->earned,
            'earned_order' => (float) $credit->earned,
            'deducted' => $credit->deducted,
            'deducted_order' => (float) $credit->deducted,
            'balance' => $credit->balance,
            'balance_order' => (float) $credit->balance,
            'as_of' => $credit->as_of ? Carbon::parse($credit->as_of)->format('F Y') : '-',
            'as_of_order' => $credit->as_of,
        ];
    }

    protected function formatTopTimelogCards(Collection $rows, string $field, string $suffix): array
    {
        return $rows
            ->filter()
            ->take(3)
            ->map(function ($row) use ($field, $suffix) {
                return [
                    'employee' => $row->employee_name ?: $row->employee_no,
                    'value' => $suffix === 'duration'
                        ? $this->minutesToHoursLabel((int) ($row->{$field} ?? 0))
                        : ($row->{$field} . ' ' . $suffix),
                ];
            })
            ->values()
            ->all();
    }

    protected function chiefCacheKey(string $segment, array $context = []): string
    {
        return 'chief-corner:' . Auth::id() . ':' . $segment . ':' . md5(json_encode($context));
    }

    protected function employeeNameSql(string $alias): string
    {
        return "TRIM(CONCAT(COALESCE({$alias}.lastname, ''), CASE WHEN COALESCE({$alias}.lastname, '') <> '' AND COALESCE({$alias}.firstname, '') <> '' THEN ', ' ELSE '' END, COALESCE({$alias}.firstname, '')))";
    }

    protected function formatEmployeeName(?string $firstname, ?string $lastname, ?string $fallback = ''): string
    {
        $firstname = trim((string) $firstname);
        $lastname = trim((string) $lastname);

        if ($lastname !== '' && $firstname !== '') {
            return "{$lastname}, {$firstname}";
        }

        return $lastname !== '' ? $lastname : ($firstname !== '' ? $firstname : trim((string) $fallback));
    }

    protected function employeeSortValue(?string $lastname, ?string $firstname, ?string $fallback = ''): string
    {
        $lastname = mb_strtolower(trim((string) $lastname));
        $firstname = mb_strtolower(trim((string) $firstname));
        $fallback = mb_strtolower(trim((string) $fallback));

        if ($lastname !== '' || $firstname !== '') {
            return trim($lastname . ' ' . $firstname);
        }

        return $fallback;
    }

    protected function timelogStatBreakdownPayload(object $row, string $field, string $format): array
    {
        $typeMap = [
            'late_minutes' => ['key' => 'late_breakdown', 'label' => 'Late'],
            'undertime_minutes' => ['key' => 'undertime_breakdown', 'label' => 'Undertime'],
            'leave_days' => ['key' => 'leave_breakdown', 'label' => 'Leave'],
            'offset_days' => ['key' => 'offset_breakdown', 'label' => 'Offset'],
            'so_days' => ['key' => 'so_breakdown', 'label' => 'Special Order'],
            'lto_days' => ['key' => 'lto_breakdown', 'label' => 'LTO'],
        ];

        $config = $typeMap[$field] ?? ['key' => null, 'label' => 'Details'];
        $items = $config['key'] ? array_values($row->{$config['key']} ?? []) : [];
        $value = $row->{$field} ?? 0;

        return [
            'id' => Str::uuid()->toString(),
            'title' => $config['label'] . ' Breakdown',
            'employee' => $row->employee_name ?: $row->employee_no,
            'value' => $format === 'duration'
                ? $this->minutesToHoursLabel((int) $value)
                : $this->formatDayCount((float) $value),
            'items' => $items,
            'empty_message' => 'No breakdown available for this record.',
        ];
    }

    protected function timelogDurationBreakdownItem(string $date, int $minutes, array $details = []): array
    {
        return [
            'date' => Carbon::parse($date)->format('M d, Y'),
            'value' => $this->minutesToHoursLabel($minutes),
            'details' => collect($details)->filter()->join(' | '),
        ];
    }

    protected function timelogDayBreakdownItem(string $date, string $remark): array
    {
        return [
            'date' => Carbon::parse($date)->format('M d, Y'),
            'value' => Str::contains(Str::lower($remark), ['morning', 'afternoon']) ? '0.5 day' : '1 day',
            'details' => $remark,
        ];
    }

    protected function formatTimelogTime(?string $time): string
    {
        return filled($time) ? Carbon::parse($time)->format('h:i A') : '--';
    }

    protected function expectedShiftStartLabel($shiftId, array &$shiftCache): string
    {
        if (!$shiftId) {
            return '--';
        }

        if (!isset($shiftCache[$shiftId])) {
            $shiftCache[$shiftId] = DB::table('shifts')->find($shiftId);
        }

        return $this->formatTimelogTime($shiftCache[$shiftId]?->start_time);
    }

    protected function expectedShiftEndLabel($shiftId, array &$shiftCache): string
    {
        if (!$shiftId) {
            return '--';
        }

        if (!isset($shiftCache[$shiftId])) {
            $shiftCache[$shiftId] = DB::table('shifts')->find($shiftId);
        }

        return $this->formatTimelogTime($shiftCache[$shiftId]?->end_time);
    }
}
