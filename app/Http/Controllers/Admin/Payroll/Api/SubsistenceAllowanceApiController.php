<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use App\Enums\FnEnum;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubsistenceAllowanceApiController extends Controller
{
    public function __construct(private DailyTimeRecordService $daily_time_record_service)
    {
    }

    public function index(Request $request)
    {
        $today = Carbon::now();

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $month = (int) ($validated['month'] ?? $today->month);
        $year = (int) ($validated['year'] ?? $today->year);
        $cutoff = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $latestOrgDate = DB::table('employee_organization')
            ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
            ->whereDate('effectivity_date', '<=', $cutoff)
            ->groupBy('employee_no');

        $latestOrgId = DB::table('employee_organization')
            ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
            ->groupBy('employee_no', 'effectivity_date');

        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                $join->on('ei.employee_no', '=', 'latest_org_date.employee_no');
            })
            ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                    ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
            })
            ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('subsistence_allowance_records as sar', function ($join) use ($month, $year) {
                $join->on('ei.employee_no', '=', 'sar.employee_no')
                    ->where('sar.month', $month)
                    ->where('sar.year', $year);
            })
            ->where('ei.account_status', 'active')
            ->where('ei.isDeleted', false)
            ->where('eo.employment_type_id', EmploymentTypesEnum::REGULAR->value)
            ->select(
                'ei.employee_no',
                'ei.user_id',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'p.name as position',
                'sar.id as record_id',
                'sar.full_day_count',
                'sar.half_day_count',
                'sar.below_four_hours_count',
                'sar.actual_days',
                'sar.deduction_count',
                'sar.deduction_amount',
                'sar.computed_amount',
                'sar.required_facility_service',
                'sar.available_at_all_times',
                'sar.may_leave_breaks',
                'sar.on_leave',
                'sar.on_official_travel',
                'sar.provided_meals',
                'sar.is_eligible',
                'sar.eligibility_details',
                'sar.remarks'
            )
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->get();

        $records = [];

        $employees = $employees->map(function ($employee) use ($month, $year, &$records) {
            $row = $this->mapEmployee($employee, $month, $year);
            $records[] = $row['_record'];
            unset($row['_record']);

            return $row;
        });

        if (!empty($records)) {
            DB::table('subsistence_allowance_records')->upsert(
                $records,
                ['employee_no', 'month', 'year'],
                [
                    'full_day_count',
                    'half_day_count',
                    'below_four_hours_count',
                    'actual_days',
                    'deduction_count',
                    'deduction_amount',
                    'computed_amount',
                    'required_facility_service',
                    'available_at_all_times',
                    'may_leave_breaks',
                    'on_leave',
                    'on_official_travel',
                    'provided_meals',
                    'is_eligible',
                    'eligibility_details',
                    'remarks',
                    'updated_at',
                ]
            );

            $recordIds = DB::table('subsistence_allowance_records')
                ->where('month', $month)
                ->where('year', $year)
                ->whereIn('employee_no', $employees->pluck('employee_no')->all())
                ->pluck('id', 'employee_no');

            $employees = $employees->map(function ($employee) use ($recordIds) {
                $employee['record_id'] = $recordIds[$employee['employee_no']] ?? $employee['record_id'];

                return $employee;
            });
        }

        return response()->json([
            'data' => $employees,
            'month' => $month,
            'year' => $year,
            'status' => 'success',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => ['required', 'string', 'exists:employee_information,employee_no'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'full_day_count' => ['nullable', 'numeric', 'min:0'],
            'half_day_count' => ['nullable', 'numeric', 'min:0'],
            'below_four_hours_count' => ['nullable', 'numeric', 'min:0'],
            'deduction_amount' => [
                'nullable',
                'numeric',
                'min:0',
                fn ($attribute, $value, $fail) => fmod((float) $value, 75.0) !== 0.0
                    ? $fail('The deduction must be divisible by 0.5 day increments.')
                    : null,
            ],
            'required_facility_service' => ['required', 'boolean'],
            'available_at_all_times' => ['required', 'boolean'],
            'may_leave_breaks' => ['required', 'boolean'],
            'on_leave' => ['required', 'boolean'],
            'on_official_travel' => ['required', 'boolean'],
            'provided_meals' => ['required', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $employee = DB::table('employee_information')
            ->where('employee_no', $validated['employee_no'])
            ->first();

        $validated = array_merge(
            $validated,
            $this->actualPresencePayload($employee?->user_id, (int) $validated['month'], (int) $validated['year'])
        );
        $computed = $this->computeAllowance($validated);

        $identity = [
            'employee_no' => $validated['employee_no'],
            'month' => (int) $validated['month'],
            'year' => (int) $validated['year'],
        ];

        $values = [
            'full_day_count' => $computed['full_day_count'],
            'half_day_count' => $computed['half_day_count'],
            'below_four_hours_count' => $computed['below_four_hours_count'],
            'actual_days' => $computed['actual_days'],
            'deduction_count' => $computed['deduction_count'],
            'deduction_amount' => $computed['deduction_amount'],
            'computed_amount' => $computed['computed_amount'],
            'required_facility_service' => (bool) $validated['required_facility_service'],
            'available_at_all_times' => (bool) $validated['available_at_all_times'],
            'may_leave_breaks' => (bool) $validated['may_leave_breaks'],
            'on_leave' => (bool) $validated['on_leave'],
            'on_official_travel' => (bool) $validated['on_official_travel'],
            'provided_meals' => (bool) $validated['provided_meals'],
            'is_eligible' => $computed['is_eligible'],
            'eligibility_details' => json_encode($computed['eligibility_details']),
            'remarks' => $validated['remarks'] ?? null,
            'updated_at' => now(),
        ];

        $exists = DB::table('subsistence_allowance_records')->where($identity)->exists();

        DB::table('subsistence_allowance_records')->updateOrInsert(
            $identity,
            $exists ? $values : array_merge($values, ['created_at' => now()])
        );

        $record = DB::table('subsistence_allowance_records')
            ->where('employee_no', $validated['employee_no'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        return response()->json([
            'data' => $record,
            'message' => 'Subsistence Allowance record saved successfully.',
            'status' => 'success',
        ]);
    }

    private function mapEmployee(object $employee, int $month, int $year): array
    {
        $details = $employee->eligibility_details
            ? json_decode($employee->eligibility_details, true)
            : null;
        $eligibility = $this->eligibilityPayload($employee);
        $deductionAmount = $employee->record_id ? (float) ($employee->deduction_amount ?? 0) : 0;
        $computed = $this->computeAllowance([
            ...$this->actualPresencePayload($employee->user_id, $month, $year),
            ...$eligibility,
            'deduction_amount' => $deductionAmount,
        ]);
        $record = $this->generatedRecordPayload($employee, $month, $year, $computed, $eligibility);

        return [
            'employee_no' => $employee->employee_no,
            'name' => $this->employeeName($employee),
            'position' => $employee->position ?? 'No position',
            'month' => $month,
            'year' => $year,
            'record_id' => $employee->record_id,
            'full_day_count' => $computed['full_day_count'],
            'half_day_count' => $computed['half_day_count'],
            'below_four_hours_count' => $computed['below_four_hours_count'],
            'actual_days' => $computed['actual_days'],
            'deduction_count' => $computed['deduction_count'],
            'deduction_amount' => $computed['deduction_amount'],
            'computed_amount' => $computed['computed_amount'],
            'required_facility_service' => $eligibility['required_facility_service'],
            'available_at_all_times' => $eligibility['available_at_all_times'],
            'may_leave_breaks' => $eligibility['may_leave_breaks'],
            'on_leave' => $eligibility['on_leave'],
            'on_official_travel' => $eligibility['on_official_travel'],
            'provided_meals' => $eligibility['provided_meals'],
            'is_eligible' => $computed['is_eligible'],
            'eligibility_details' => $details,
            'remarks' => $employee->remarks,
            '_record' => $record,
        ];
    }

    private function eligibilityPayload(object $employee): array
    {
        if (!$employee->record_id) {
            return [
                'required_facility_service' => true,
                'available_at_all_times' => true,
                'may_leave_breaks' => false,
                'on_leave' => false,
                'on_official_travel' => false,
                'provided_meals' => false,
            ];
        }

        return [
            'required_facility_service' => (bool) $employee->required_facility_service,
            'available_at_all_times' => (bool) $employee->available_at_all_times,
            'may_leave_breaks' => (bool) $employee->may_leave_breaks,
            'on_leave' => (bool) $employee->on_leave,
            'on_official_travel' => (bool) $employee->on_official_travel,
            'provided_meals' => (bool) $employee->provided_meals,
        ];
    }

    private function generatedRecordPayload(object $employee, int $month, int $year, array $computed, array $eligibility): array
    {
        return [
            'employee_no' => $employee->employee_no,
            'month' => $month,
            'year' => $year,
            'full_day_count' => $computed['full_day_count'],
            'half_day_count' => $computed['half_day_count'],
            'below_four_hours_count' => $computed['below_four_hours_count'],
            'actual_days' => $computed['actual_days'],
            'deduction_count' => $computed['deduction_count'],
            'deduction_amount' => $computed['deduction_amount'],
            'computed_amount' => $computed['computed_amount'],
            'required_facility_service' => $eligibility['required_facility_service'],
            'available_at_all_times' => $eligibility['available_at_all_times'],
            'may_leave_breaks' => $eligibility['may_leave_breaks'],
            'on_leave' => $eligibility['on_leave'],
            'on_official_travel' => $eligibility['on_official_travel'],
            'provided_meals' => $eligibility['provided_meals'],
            'is_eligible' => $computed['is_eligible'],
            'eligibility_details' => json_encode($computed['eligibility_details']),
            'remarks' => $employee->remarks,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function computeAllowance(array $payload): array
    {
        $fullDays = round((float) ($payload['full_day_count'] ?? 0), 2);
        $halfDays = round((float) ($payload['half_day_count'] ?? 0), 2);
        $belowFourHours = round((float) ($payload['below_four_hours_count'] ?? 0), 2);
        $deductionAmount = max(round((float) ($payload['deduction_amount'] ?? 0), 2), 0);

        $eligible = (bool) $payload['required_facility_service']
            && (bool) $payload['available_at_all_times']
            && !(bool) $payload['may_leave_breaks']
            && !(bool) $payload['on_leave']
            && !(bool) $payload['on_official_travel']
            && !(bool) $payload['provided_meals'];

        $grossAmount = $eligible ? round(($fullDays * 150) + ($halfDays * 75), 2) : 0;
        $deductionCount = round($deductionAmount / 150, 2);
        $computedAmount = max(round($grossAmount - $deductionAmount, 2), 0);

        return [
            'full_day_count' => $fullDays,
            'half_day_count' => $halfDays,
            'below_four_hours_count' => $belowFourHours,
            'actual_days' => round($fullDays + ($halfDays * 0.5), 2),
            'deduction_count' => $deductionCount,
            'deduction_amount' => $deductionAmount,
            'computed_amount' => $computedAmount,
            'is_eligible' => $eligible,
            'eligibility_details' => [
                'required_facility_service' => (bool) $payload['required_facility_service'],
                'available_at_all_times' => (bool) $payload['available_at_all_times'],
                'may_leave_breaks' => (bool) $payload['may_leave_breaks'],
                'on_leave' => (bool) $payload['on_leave'],
                'on_official_travel' => (bool) $payload['on_official_travel'],
                'provided_meals' => (bool) $payload['provided_meals'],
            ],
        ];
    }

    private function actualPresencePayload(?int $userId, int $month, int $year): array
    {
        if (!$userId) {
            return $this->countsFromActualPresence(0);
        }

        $period = Carbon::create($year, $month, 1);
        $payload = [
            'user_id' => $userId,
            'startDate' => $period->copy()->startOfMonth()->format('Y-m-d'),
            'endDate' => $period->copy()->endOfMonth()->format('Y-m-d'),
        ];

        $dtr = $this->daily_time_record_service->getDTR($payload);
        $total_summary_of_dtr = $dtr['payroll_value'];
        $actual_presence = (float) ($total_summary_of_dtr['actual_presence'] ?? 0);

        return $this->countsFromActualPresence($actual_presence);
    }

    private function countsFromActualPresence(float $actualPresence): array
    {
        $actualPresence = max(round($actualPresence * 2) / 2, 0);
        $fullDays = floor($actualPresence);
        $halfDays = $actualPresence - $fullDays >= 0.5 ? 1 : 0;

        return [
            'full_day_count' => $fullDays,
            'half_day_count' => $halfDays,
            'below_four_hours_count' => 0,
        ];
    }

    private function timelogCountsByUser(array $userIds, int $month, int $year): array
    {
        $userIds = collect($userIds)->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        $period = Carbon::create($year, $month, 1);
        $dailyLogs = DB::table('timelogs')
            ->where('is_active', true)
            ->whereIn('user_id', $userIds->all())
            ->whereBetween('date_time', [
                $period->copy()->startOfMonth()->startOfDay(),
                $period->copy()->endOfMonth()->endOfDay(),
            ])
            ->whereIn('fn', [
                FnEnum::TimeIn->value,
                FnEnum::TimeOut->value,
                FnEnum::BreakOut->value,
                FnEnum::BreakIn->value,
            ])
            ->selectRaw('user_id')
            ->selectRaw('DATE(date_time) as log_date')
            ->selectRaw('MIN(CASE WHEN fn = ? THEN date_time END) as time_in', [FnEnum::TimeIn->value])
            ->selectRaw('MAX(CASE WHEN fn = ? THEN date_time END) as time_out', [FnEnum::TimeOut->value])
            ->selectRaw('MAX(CASE WHEN fn = ? THEN date_time END) as break_out', [FnEnum::BreakOut->value])
            ->selectRaw('MIN(CASE WHEN fn = ? THEN date_time END) as break_in', [FnEnum::BreakIn->value])
            ->groupBy('user_id', DB::raw('DATE(date_time)'))
            ->get();

        $counts = [];

        foreach ($dailyLogs as $log) {
            $counts[$log->user_id] ??= $this->emptyTimelogCounts();
            $minutes = $this->workMinutesFromLog($log);

            if ($minutes >= 480) {
                $counts[$log->user_id]['full_day_count']++;
            } elseif ($minutes >= 240) {
                $counts[$log->user_id]['half_day_count']++;
            } elseif ($minutes > 0) {
                $counts[$log->user_id]['below_four_hours_count']++;
            }
        }

        return $userIds
            ->mapWithKeys(fn ($userId) => [$userId => $counts[$userId] ?? $this->emptyTimelogCounts()])
            ->all();
    }

    private function emptyTimelogCounts(): array
    {
        return [
            'full_day_count' => 0,
            'half_day_count' => 0,
            'below_four_hours_count' => 0,
        ];
    }

    private function workMinutesFromLog(object $log): int
    {
        if (!$log->time_in || !$log->time_out) {
            return 0;
        }

        $minutes = Carbon::parse($log->time_in)->diffInMinutes(Carbon::parse($log->time_out), false);

        if ($log->break_out && $log->break_in) {
            $minutes -= Carbon::parse($log->break_out)->diffInMinutes(Carbon::parse($log->break_in), false);
        }

        return max((int) $minutes, 0);
    }

    private function employeeName(object $employee): string
    {
        return trim(collect([
            $employee->firstname,
            $employee->middlename,
            $employee->lastname,
            $employee->suffix,
        ])->filter()->join(' '));
    }
}
