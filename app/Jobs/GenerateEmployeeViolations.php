<?php

namespace App\Jobs;

use App\Events\NotificationEvents;
use App\Services\DailyTimeRecordService;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateEmployeeViolations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public ?string $employeeNo = null,
        public ?string $period = null,
        public bool $shouldNotify = true,
        public array $monthExemptions = []
    ) {
        $this->period ??= now()->format('Y-m');
    }

    public function handle(DailyTimeRecordService $dailyTimeRecordService, TimelogsServices $timelogsServices): void
    {
        $month = Carbon::parse($this->period . '-01');
        $employeeNo = $this->employeeNo ?: $this->employeeNo();
        $settings = DB::table('violation_settings')
            ->where('is_active', true)
            ->orderBy('violation_type')
            ->orderBy('threshold')
            ->get();

        DB::transaction(function () use ($settings, $month, $employeeNo, $dailyTimeRecordService, $timelogsServices) {
            $generatedKeys = [];

            foreach ($settings as $setting) {
                $evaluation = $this->evaluate($setting, $month, $dailyTimeRecordService, $timelogsServices);

                if (! $evaluation) {
                    continue;
                }

                $attributes = [
                    'user_id' => $this->userId,
                    'employee_no' => $employeeNo,
                    'month' => $month->month,
                    'year' => $month->year,
                    'violation_type' => $this->canonicalViolationType((string) $setting->violation_type),
                    'action_name' => $this->actionName($setting),
                ];

                $this->updateOrInsertViolation($attributes, [
                    'violation_setting_id' => $setting->id,
                    'threshold' => $setting->threshold,
                    'occurrence_count' => $evaluation['count'],
                    'description' => $evaluation['description'],
                    'details' => json_encode($evaluation['details'] ?? [], JSON_UNESCAPED_SLASHES),
                    'generated_at' => now(),
                    'updated_at' => now(),
                ]);

                $generatedKeys[] = $attributes;
            }

            $this->deleteStaleViolations($this->userId, $month, $generatedKeys);
        });

        if ($this->shouldNotify) {
            $total = DB::table('employee_violations')
                ->where('user_id', $this->userId)
                ->where('month', $month->month)
                ->where('year', $month->year)
                ->count();

            event(new NotificationEvents('system', 'HRIS', $this->userId, [
                'message' => $total > 0
                    ? "%bViolation check complete.%b We found {$total} attendance violation item(s) for " . $month->format('F Y') . '.'
                    : '%bViolation check complete.%b No attendance violations were found for ' . $month->format('F Y') . '.',
                'link' => route('dashboard.index'),
            ]));
        }
    }

    public function failed(Throwable $exception): void
    {
        if (! $this->shouldNotify) {
            return;
        }

        event(new NotificationEvents('system', 'HRIS', $this->userId, [
            'message' => "%bViolation check could not be completed.%b Please try again later or contact HR if the issue continues.",
            'link' => route('dashboard.index'),
        ]));
    }

    private function employeeNo(): ?string
    {
        return DB::table('employee_information')
            ->where('user_id', $this->userId)
            ->value('employee_no');
    }

    private function actionName(object $setting): string
    {
        $violationType = $this->canonicalViolationType((string) $setting->violation_type);

        return match ($violationType) {
            'Tardiness / Late', 'Undertime', 'Unauthorized Absence' => $violationType,
            default => (string) $setting->action_name,
        };
    }

    private function updateOrInsertViolation(array $attributes, array $values): void
    {
        $ids = $this->matchingViolationQuery($attributes)
            ->orderBy('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            DB::table('employee_violations')->insert(array_merge($attributes, $values, [
                'status' => 'unseen',
                'seen_at' => null,
                'created_at' => now(),
            ]));

            return;
        }

        DB::table('employee_violations')
            ->where('id', $ids->first())
            ->update($values);

        if ($ids->count() > 1) {
            DB::table('employee_violations')
                ->whereIn('id', $ids->skip(1)->all())
                ->delete();
        }
    }

    private function deleteStaleViolations(int $userId, Carbon $month, array $generatedKeys): void
    {
        $query = DB::table('employee_violations')
            ->where('user_id', $userId)
            ->where('month', $month->month)
            ->where('year', $month->year);

        if (empty($generatedKeys)) {
            $query->delete();

            return;
        }

        $idsToKeep = collect($generatedKeys)
            ->flatMap(fn (array $attributes) => $this->matchingViolationQuery($attributes)->pluck('id'))
            ->all();

        $query->whereNotIn('id', $idsToKeep)->delete();
    }

    private function matchingViolationQuery(array $attributes)
    {
        $query = DB::table('employee_violations');

        foreach ($attributes as $column => $value) {
            is_null($value)
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        return $query;
    }

    private function evaluate(
        object $setting,
        Carbon $month,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): ?array {
        if (! empty($setting->metric) && ! empty($setting->period_type)) {
            return $this->structuredEvaluation($setting, $month, $dailyTimeRecordService, $timelogsServices);
        }

        $violationType = $this->canonicalViolationType((string) $setting->violation_type);

        return match ($violationType) {
            'Tardiness / Late' => $this->monthlyEvaluation($setting, $month, 'lates', $dailyTimeRecordService, $timelogsServices),
            'Undertime' => $this->monthlyEvaluation($setting, $month, 'undertimes', $dailyTimeRecordService, $timelogsServices),
            'Unauthorized Absence' => $this->monthlyEvaluation($setting, $month, 'unauthorized_absences', $dailyTimeRecordService, $timelogsServices),
            'Discrepancy / Missing Timelog' => $this->monthlyEvaluation($setting, $month, 'missing_timelogs', $dailyTimeRecordService, $timelogsServices),
            'Missed Break Log' => $this->monthlyEvaluation($setting, $month, 'missed_break_logs', $dailyTimeRecordService, $timelogsServices),
            'Habitual Tardiness' => $this->semesterEvaluation($setting, $month, 'lates', 10, $dailyTimeRecordService, $timelogsServices),
            'Frequent Undertime' => $this->semesterEvaluation($setting, $month, 'undertimes', 10, $dailyTimeRecordService, $timelogsServices),
            'Habitual Absenteeism' => $this->semesterEvaluation($setting, $month, 'unauthorized_absences', 3, $dailyTimeRecordService, $timelogsServices),
            'Habitual Tardiness - Consecutive' => $this->consecutiveEvaluation($setting, $month, 'lates', 10, $dailyTimeRecordService, $timelogsServices),
            'Frequent Undertime - Consecutive' => $this->consecutiveEvaluation($setting, $month, 'undertimes', 10, $dailyTimeRecordService, $timelogsServices),
            'Habitual Absenteeism - Consecutive' => $this->consecutiveEvaluation($setting, $month, 'unauthorized_absences', 3, $dailyTimeRecordService, $timelogsServices),
            default => null,
        };
    }

    private function structuredEvaluation(
        object $setting,
        Carbon $month,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): ?array {
        $periodType = $setting->period_type;
        $requiredMonths = (int) ($setting->required_months ?? 1);
        $monthlyThreshold = (float) ($setting->monthly_threshold ?? $setting->threshold);
        $operator = $setting->threshold_operator ?? '>=';

        if (in_array($periodType, ['monthly', 'incident'], true)) {
            $stats = $this->monthlyStats($month, $dailyTimeRecordService, $timelogsServices);
            $count = (float) ($stats[$setting->metric] ?? 0);

            if (! $this->passesThreshold($count, $monthlyThreshold, $operator)) {
                return null;
            }

            return [
                'count' => (int) $count,
                'description' => $this->description($setting, (int) $count, $month, $this->detailsForMetric($stats, $setting->metric)),
                'details' => $this->detailsForMetric($stats, $setting->metric),
            ];
        }

        $months = match ($periodType) {
            'semester' => $this->semesterMonths($month),
            'year' => collect(range(1, 12))
                ->map(fn (int $monthNumber) => $month->copy()->month($monthNumber)->startOfMonth())
                ->all(),
            default => [],
        };
        $months = $this->eligibleEvaluationMonths($months, $month);

        if (empty($months)) {
            return null;
        }

        $qualifiedMonths = $this->qualifiedStructuredMonths(
            $months,
            $setting->metric,
            $monthlyThreshold,
            $operator,
            $dailyTimeRecordService,
            $timelogsServices
        );

        if (! $this->containsMonth($qualifiedMonths, $month)) {
            return null;
        }

        $matchedMonths = (bool) ($setting->is_consecutive ?? false)
            ? $this->consecutiveRunEndingAt($qualifiedMonths, $month, $requiredMonths)
            : $qualifiedMonths;

        $count = count($matchedMonths);

        if ($count < $requiredMonths) {
            return null;
        }

        return [
            'count' => $count,
            'description' => $this->periodDescription($setting, $count, $matchedMonths, $month),
            'details' => $this->periodDetails($matchedMonths, $setting->metric, $dailyTimeRecordService, $timelogsServices),
        ];
    }

    private function monthlyEvaluation(
        object $setting,
        Carbon $month,
        string $key,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices,
        bool $strictlyGreaterThan = false
    ): ?array {
        $stats = $this->monthlyStats($month, $dailyTimeRecordService, $timelogsServices);
        $count = $stats[$key] ?? 0;
        $qualified = $strictlyGreaterThan ? $count > 2.5 : $count >= $setting->threshold;

        if (! $qualified) {
            return null;
        }

        return [
            'count' => $count,
            'description' => $this->description($setting, $count, $month, $this->detailsForMetric($stats, $key)),
            'details' => $this->detailsForMetric($stats, $key),
        ];
    }

    private function semesterEvaluation(
        object $setting,
        Carbon $month,
        string $key,
        int $monthlyThreshold,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): ?array {
        $months = $this->eligibleEvaluationMonths($this->semesterMonths($month), $month);
        $qualifiedMonths = $this->qualifiedMonths($months, $key, $monthlyThreshold, $dailyTimeRecordService, $timelogsServices);
        $count = count($qualifiedMonths);

        if ($count < $setting->threshold || ! $this->containsMonth($qualifiedMonths, $month)) {
            return null;
        }

        return [
            'count' => $count,
            'description' => $this->periodDescription($setting, $count, $qualifiedMonths, $month),
            'details' => $this->periodDetails($qualifiedMonths, $key, $dailyTimeRecordService, $timelogsServices),
        ];
    }

    private function consecutiveEvaluation(
        object $setting,
        Carbon $month,
        string $key,
        int $monthlyThreshold,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): ?array {
        $months = collect(range(1, 12))
            ->map(fn (int $monthNumber) => $month->copy()->month($monthNumber)->startOfMonth())
            ->all();
        $months = $this->eligibleEvaluationMonths($months, $month);

        $qualifiedMonths = $this->qualifiedMonths($months, $key, $monthlyThreshold, $dailyTimeRecordService, $timelogsServices);
        $run = $this->consecutiveRunEndingAt($qualifiedMonths, $month, $setting->threshold);

        if (count($run) < $setting->threshold) {
            return null;
        }

        return [
            'count' => count($run),
            'description' => $this->periodDescription($setting, count($run), $run, $month),
            'details' => $this->periodDetails($run, $key, $dailyTimeRecordService, $timelogsServices),
        ];
    }

    private function qualifiedMonths(
        array $months,
        string $key,
        int $monthlyThreshold,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): array {
        return collect($months)
            ->filter(function (Carbon $month) use ($key, $monthlyThreshold, $dailyTimeRecordService, $timelogsServices) {
                $stats = $this->monthlyStats($month, $dailyTimeRecordService, $timelogsServices);

                return ($stats[$key] ?? 0) >= $monthlyThreshold;
            })
            ->values()
            ->all();
    }

    private function qualifiedStructuredMonths(
        array $months,
        string $key,
        float $monthlyThreshold,
        string $operator,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): array {
        return collect($months)
            ->filter(function (Carbon $month) use ($key, $monthlyThreshold, $operator, $dailyTimeRecordService, $timelogsServices) {
                $stats = $this->monthlyStats($month, $dailyTimeRecordService, $timelogsServices);

                return $this->passesThreshold((float) ($stats[$key] ?? 0), $monthlyThreshold, $operator);
            })
            ->values()
            ->all();
    }

    private function passesThreshold(float $count, float $threshold, string $operator): bool
    {
        return match ($operator) {
            '>' => $count > $threshold,
            '<' => $count < $threshold,
            '<=' => $count <= $threshold,
            '=' => $count == $threshold,
            default => $count >= $threshold,
        };
    }

    private function consecutiveRunEndingAt(array $months, Carbon $evaluationMonth, int $requiredLength): array
    {
        $run = [];

        foreach ($months as $month) {
            $previous = end($run);

            if (! $previous || $previous->copy()->addMonth()->isSameMonth($month)) {
                $run[] = $month;
            } else {
                $run = [$month];
            }
        }

        return count($run) >= $requiredLength && $this->containsMonth($run, $evaluationMonth)
            ? $run
            : [];
    }

    private function containsMonth(array $months, Carbon $target): bool
    {
        return collect($months)->contains(fn (Carbon $month) => $month->isSameMonth($target));
    }

    private function semesterMonths(Carbon $month): array
    {
        $startMonth = $month->month <= 6 ? 1 : 7;

        return collect(range($startMonth, $startMonth + 5))
            ->map(fn (int $monthNumber) => $month->copy()->month($monthNumber)->startOfMonth())
            ->all();
    }

    private function eligibleEvaluationMonths(array $months, Carbon $evaluationMonth): array
    {
        return collect($months)
            ->filter(fn (Carbon $month) => $month->lte($evaluationMonth) && ! $this->isMonthExempted($month))
            ->values()
            ->all();
    }

    private function isMonthExempted(Carbon $month): bool
    {
        return collect($this->monthExemptions)->contains(function ($exemption) use ($month) {
            if (is_numeric($exemption)) {
                return (int) $exemption === $month->month;
            }

            return (string) $exemption === $month->format('Y-m');
        });
    }

    private function monthlyStats(Carbon $month, DailyTimeRecordService $dailyTimeRecordService, TimelogsServices $timelogsServices): array
    {
        static $cache = [];

        $cacheKey = $this->userId . ':' . $month->format('Y-m');

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $startDate = $month->copy()->startOfMonth()->toDateString();
        $endDate = $month->copy()->endOfMonth()->toDateString();
        $dtr = $dailyTimeRecordService->getDTR([
            'user_id' => $this->userId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $cache[$cacheKey] = $this->counts(collect($dtr['computedData'] ?? []));
    }

    private function counts(Collection $rows): array
    {
        $counts = [
            'lates' => 0,
            'undertimes' => 0,
            'unauthorized_absences' => 0,
            'missing_timelogs' => 0,
            'missed_break_logs' => 0,
            '_details' => [
                'lates' => [],
                'undertimes' => [],
                'unauthorized_absences' => [],
                'missing_timelogs' => [],
                'missed_break_logs' => [],
            ],
        ];

        foreach ($rows as $row) {
            $tardinessMinutes = max((int) ($row['tardiness_minutes'] ?? 0), 0);
            $undertimeMinutes = max((int) ($row['undertime_minutes'] ?? 0), 0);
            $remarks = collect($row['remarks'] ?? [])
                ->flatten()
                ->map(fn ($remark) => strtolower((string) $remark))
                ->implode(' ');
            $hasMissedBreakLog = $this->hasMissedBreakLog($row);

            if ($tardinessMinutes > 0) {
                $this->addUniqueDetail($counts, 'lates', $this->attendanceDetail($row, 'Tardiness', [
                    'minutes' => $tardinessMinutes,
                    'lost_hours' => $this->minutesToHoursLabel($tardinessMinutes),
                ]));
            }

            if ($undertimeMinutes > 0) {
                $this->addUniqueDetail($counts, 'undertimes', $this->attendanceDetail($row, 'Undertime', [
                    'minutes' => $undertimeMinutes,
                    'lost_hours' => $this->minutesToHoursLabel($undertimeMinutes),
                ]));
            }

            if (str_contains($remarks, 'considered absent') || str_contains($remarks, 'absent')) {
                $this->addUniqueDetail($counts, 'unauthorized_absences', $this->attendanceDetail($row, 'Unauthorized absence'));
            }

            if ($hasMissedBreakLog) {
                $this->addUniqueDetail($counts, 'missed_break_logs', $this->attendanceDetail($row, 'Missed break log', [
                    'reason' => $this->missedBreakReason($row),
                    'missing_punches' => $this->missingBreakPunches($row),
                ]));
            }

            if (! $hasMissedBreakLog && ! str_contains($remarks, 'considered absent') && (str_contains($remarks, 'discrepancy') || str_contains($remarks, 'incomplete log'))) {
                $this->addUniqueDetail($counts, 'missing_timelogs', $this->attendanceDetail($row, 'Discrepancy or incomplete log', [
                    'reason' => $this->missingTimelogReason($row),
                    'discrepancy_reasons' => $row['discrepancy_reasons'] ?? [],
                    'missing_punches' => $this->missingPunches($row),
                ]));
            }
        }

        return $counts;
    }

    private function addUniqueDetail(array &$stats, string $key, array $detail): void
    {
        $date = $detail['date'] ?? null;

        if ($date && collect($stats['_details'][$key] ?? [])->contains(fn (array $item) => ($item['date'] ?? null) === $date)) {
            return;
        }

        $stats[$key] = ($stats[$key] ?? 0) + 1;
        $stats['_details'][$key][] = $detail;
    }

    private function detailsForMetric(array $stats, string $key): array
    {
        $items = collect($stats['_details'][$key] ?? [])
            ->sortBy('date')
            ->values()
            ->all();
        $totalMinutes = in_array($key, ['lates', 'undertimes'], true)
            ? collect($items)->sum(fn (array $item) => (int) ($item['minutes'] ?? 0))
            : null;

        return [
            'metric' => $key,
            'summary' => $this->detailsSummary([
                'items' => $items,
                'total_minutes' => $totalMinutes,
            ]),
            'dates' => collect($items)->pluck('date')->filter()->unique()->values()->all(),
            'items' => $items,
            'total_minutes' => $totalMinutes,
            'total_lost_hours' => is_null($totalMinutes) ? null : $this->minutesToHoursLabel($totalMinutes),
        ];
    }

    private function periodDetails(
        array $months,
        string $key,
        DailyTimeRecordService $dailyTimeRecordService,
        TimelogsServices $timelogsServices
    ): array {
        $monthDetails = collect($months)
            ->map(function (Carbon $month) use ($key, $dailyTimeRecordService, $timelogsServices) {
                $stats = $this->monthlyStats($month, $dailyTimeRecordService, $timelogsServices);
                $details = $this->detailsForMetric($stats, $key);

                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->format('F Y'),
                    'count' => $stats[$key] ?? 0,
                    'dates' => $details['dates'],
                    'items' => $details['items'],
                ];
            })
            ->values()
            ->all();

        return [
            'metric' => $key,
            'summary' => 'Qualifying months: ' . collect($monthDetails)->pluck('label')->implode(', ') . '.',
            'months' => $monthDetails,
        ];
    }

    private function attendanceDetail(array $row, string $type, array $extra = []): array
    {
        return array_merge([
            'date' => $this->formatDateValue($row['date'] ?? null),
            'type' => $type,
            'time_in' => $this->formatTimeValue($row['time_in'] ?? null),
            'time_out' => $this->formatTimeValue($row['time_out'] ?? null),
            'break' => $row['break'] ?? null,
            'remarks' => $this->remarks($row['remarks'] ?? []),
        ], $extra);
    }

    private function missingTimelogReason(array $row): string
    {
        $discrepancyReasons = collect($row['discrepancy_reasons'] ?? [])
            ->map(fn ($reason) => trim((string) $reason))
            ->filter()
            ->values();

        if ($discrepancyReasons->isNotEmpty()) {
            return $discrepancyReasons->implode('; ');
        }

        $missingPunches = $this->missingPunches($row);

        if ($missingPunches !== []) {
            return 'Missing ' . collect($missingPunches)
                ->map(fn (string $label) => str($label)->lower()->toString())
                ->implode(', ');
        }

        $remarks = $this->remarks($row['remarks'] ?? []);

        return $remarks === [] ? 'Incomplete or invalid timelog sequence' : implode(', ', $remarks);
    }

    private function hasMissedBreakLog(array $row): bool
    {
        if (empty($row['time_in']) || empty($row['time_out'])) {
            return false;
        }

        return $this->missingBreakPunches($row) !== [];
    }

    private function missedBreakReason(array $row): string
    {
        $missingPunches = $this->missingBreakPunches($row);

        if ($missingPunches === []) {
            return 'Complete break log';
        }

        return 'Missing ' . collect($missingPunches)
            ->map(fn (string $label) => str($label)->lower()->toString())
            ->implode(', ');
    }

    private function missingBreakPunches(array $row): array
    {
        return collect([
            'break_out' => 'Break out',
            'break_in' => 'Break in',
        ])
            ->filter(fn (string $label, string $key) => ! $this->hasBreakPunch($row, $key))
            ->values()
            ->all();
    }

    private function hasBreakPunch(array $row, string $key): bool
    {
        if (! empty($row[$key])) {
            return true;
        }

        $break = trim((string) ($row['break'] ?? ''));

        if ($break === '' || ! str_contains($break, ' to ')) {
            return false;
        }

        [$breakOut, $breakIn] = array_map('trim', explode(' to ', $break, 2));
        $value = $key === 'break_out' ? $breakOut : $breakIn;

        return $value !== '' && ! str_contains($value, '--');
    }

    private function missingPunches(array $row): array
    {
        return collect([
            'time_in' => 'Time in',
            'time_out' => 'Time out',
        ])
            ->filter(fn (string $label, string $key) => empty($row[$key]))
            ->values()
            ->all();
    }

    private function remarks(array|string|null $remarks): array
    {
        return collect($remarks ?? [])
            ->flatten()
            ->map(fn ($remark) => trim((string) $remark))
            ->filter()
            ->values()
            ->all();
    }

    private function formatDateValue(mixed $value): ?string
    {
        return empty($value) ? null : Carbon::parse($value)->toDateString();
    }

    private function formatTimeValue(mixed $value): ?string
    {
        return empty($value) ? null : Carbon::parse($value)->format('h:i A');
    }

    private function minutesToHoursLabel(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function detailsSummary(array $details): ?string
    {
        if (! empty($details['summary'])) {
            return $details['summary'];
        }

        $totalMinutes = $details['total_minutes'] ?? null;
        $dates = collect($details['dates'] ?? collect($details['items'] ?? [])->pluck('date'))
            ->filter()
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return is_null($totalMinutes) ? null : 'Total time: ' . $this->minutesToDurationLabel((int) $totalMinutes) . '.';
        }

        $shownDates = $dates
            ->take(5)
            ->map(fn (string $date) => Carbon::parse($date)->format('M j'))
            ->implode(', ');
        $remaining = $dates->count() - 5;

        $dateSummary = 'Dates: ' . $shownDates . ($remaining > 0 ? " and {$remaining} more" : '') . '.';

        return is_null($totalMinutes)
            ? $dateSummary
            : 'Total time: ' . $this->minutesToDurationLabel((int) $totalMinutes) . '. ' . $dateSummary;
    }

    private function minutesToDurationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . str('hour')->plural($hours);
        }

        if ($remainingMinutes > 0 || $parts === []) {
            $parts[] = $remainingMinutes . ' ' . str('minute')->plural($remainingMinutes);
        }

        return implode(' and ', $parts);
    }

    private function description(object $setting, int $count, Carbon $month, array $details = []): string
    {
        $occurrence = str('occurrence')->plural($count);
        $summary = $this->detailsSummary($details);
        $violationType = $this->canonicalViolationType((string) $setting->violation_type);

        return "{$count} {$occurrence} of {$violationType} recorded for "
            . $month->format('F Y')
            . '.'
            . ($summary ? " {$summary}" : '');
    }

    private function periodDescription(object $setting, int $count, array $months, Carbon $month): string
    {
        $monthList = collect($months)
            ->map(fn (Carbon $qualifiedMonth) => $qualifiedMonth->format('F'))
            ->implode(', ');
        $occurrence = str('qualifying month')->plural($count);
        $violationType = $this->canonicalViolationType((string) $setting->violation_type);

        return "{$violationType} generated for {$month->year}: {$count} {$occurrence}"
            . ($monthList ? " ({$monthList})." : '.');
    }

    private function canonicalViolationType(string $violationType): string
    {
        $normalized = str($violationType)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->upper()
            ->toString();

        return match ($normalized) {
            'TARDINESS / LATE', 'TARDINESS/LATE', 'LATE', 'LATES' => 'Tardiness / Late',
            'HABITUAL TARDINESS' => 'Habitual Tardiness',
            'HABITUAL TARDINESS - CONSECUTIVE', 'HABITUAL TARDINESS-CONSECUTIVE' => 'Habitual Tardiness - Consecutive',
            'UNDERTIME' => 'Undertime',
            'FREQUENT UNDERTIME' => 'Frequent Undertime',
            'FREQUENT UNDERTIME - CONSECUTIVE', 'FREQUENT UNDERTIME-CONSECUTIVE' => 'Frequent Undertime - Consecutive',
            'UNAUTHORIZED ABSENCE', 'ABSENCE', 'ABSENCES' => 'Unauthorized Absence',
            'HABITUAL ABSENTEEISM' => 'Habitual Absenteeism',
            'HABITUAL ABSENTEEISM - CONSECUTIVE', 'HABITUAL ABSENTEEISM-CONSECUTIVE' => 'Habitual Absenteeism - Consecutive',
            'DISCREPANCY / MISSING TIMELOG', 'DISCREPANCY/MISSING TIMELOG', 'MISSING TIMELOG', 'MISSING TIMELOGS', 'INCOMPLETE TIMELOGS' => 'Discrepancy / Missing Timelog',
            'MISSED BREAK LOG', 'MISSED BREAK', 'MISSING BREAK LOG', 'MISSING BREAK' => 'Missed Break Log',
            default => $violationType,
        };
    }
}
