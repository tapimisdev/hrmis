<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        $now = now();

        DB::table('violation_settings')
            ->whereIn('violation_type', [
                'Flag Ceremony Late',
                'Timelog Irregularity / Possible Falsification',
            ])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        DB::table('violation_settings')
            ->get()
            ->each(function (object $setting) use ($now) {
                $violationType = $this->canonicalViolationType((string) $setting->violation_type);
                $rule = $this->rules()[$violationType] ?? null;

                if (! $rule) {
                    return;
                }

                $payload = array_merge($rule, [
                    'violation_type' => $violationType,
                    'updated_at' => $now,
                ]);

                if (Schema::hasColumn('violation_settings', 'label')) {
                    $payload['label'] = $violationType;
                }

                DB::table('violation_settings')
                    ->where('id', $setting->id)
                    ->update($this->filterColumns($payload));
            });
    }

    public function down(): void
    {
        // Normalizing labels and rule metadata is intentionally not reversed.
    }

    private function filterColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('violation_settings', $column))
            ->all();
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

    private function rules(): array
    {
        return [
            'Tardiness / Late' => [
                'threshold' => 1,
                'metric' => 'lates',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness' => [
                'threshold' => 2,
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness - Consecutive' => [
                'threshold' => 2,
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Undertime' => [
                'threshold' => 1,
                'metric' => 'undertimes',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Frequent Undertime' => [
                'threshold' => 2,
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Frequent Undertime - Consecutive' => [
                'threshold' => 2,
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Unauthorized Absence' => [
                'threshold' => 1,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism' => [
                'threshold' => 3,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism - Consecutive' => [
                'threshold' => 3,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Discrepancy / Missing Timelog' => [
                'threshold' => 1,
                'metric' => 'missing_timelogs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Missed Break Log' => [
                'threshold' => 1,
                'metric' => 'missed_break_logs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
        ];
    }
};
