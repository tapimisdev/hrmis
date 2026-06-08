<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ViolationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        $now = now();

        DB::table('violation_settings')
            ->whereIn('violation_type', ['UT', 'Absences', 'Incomplete Timelogs'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        foreach ($this->rules() as $rule) {
            DB::table('violation_settings')->updateOrInsert(
                ['violation_type' => $rule['violation_type']],
                array_merge($this->filterColumns($rule), [
                    'is_active' => true,
                    'updated_at' => $now,
                ])
            );
        }
    }

    private function filterColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('violation_settings', $column))
            ->all();
    }

    private function rules(): array
    {
        return [
            [
                'violation_type' => 'Tardiness / Late',
                'rule_trigger' => 'Employee has at least one tardiness record in a month',
                'evaluation_period' => 'Count monthly',
                'action_name' => 'Tagged as Tardiness / Late',
                'threshold' => 1,
                'metric' => 'lates',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Habitual Tardiness',
                'rule_trigger' => 'Employee has 10 or more lates per month for at least 2 months in one semester',
                'evaluation_period' => 'Jan–Jun or Jul–Dec',
                'action_name' => 'Tagged as Habitual Tardiness',
                'threshold' => 2,
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Habitual Tardiness - Consecutive',
                'rule_trigger' => 'Employee has 10 or more lates for 2 consecutive months in a year',
                'evaluation_period' => 'Jan–Dec',
                'action_name' => 'Tagged as Habitual Tardiness',
                'threshold' => 2,
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            [
                'violation_type' => 'Undertime',
                'rule_trigger' => 'Employee has at least one undertime record in a month',
                'evaluation_period' => 'Count monthly',
                'action_name' => 'Tagged as Undertime',
                'threshold' => 1,
                'metric' => 'undertimes',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Frequent Undertime',
                'rule_trigger' => 'Employee has 10 or more undertimes per month for at least 2 months in one semester',
                'evaluation_period' => 'Jan–Jun or Jul–Dec',
                'action_name' => 'Tagged as Frequent Undertime',
                'threshold' => 2,
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Frequent Undertime - Consecutive',
                'rule_trigger' => 'Employee has 10 or more undertimes for 2 consecutive months in a year',
                'evaluation_period' => 'Jan–Dec',
                'action_name' => 'Tagged as  Frequent Undertime',
                'threshold' => 2,
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            [
                'violation_type' => 'Unauthorized Absence',
                'rule_trigger' => 'Employee has at least one unauthorized absence in a month',
                'evaluation_period' => 'Count monthly',
                'action_name' => 'Tagged as Unauthorized Absence',
                'threshold' => 1,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Habitual Absenteeism',
                'rule_trigger' => 'Employee has unauthorized absences exceeding 2.5 days monthly for at least 3 months in a semester',
                'evaluation_period' => 'Jan–Jun or Jul–Dec',
                'action_name' => 'Tagged as Habitual Absenteeism',
                'threshold' => 3,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Habitual Absenteeism - Consecutive',
                'rule_trigger' => 'Employee has unauthorized absences exceeding 2.5 days monthly for 3 consecutive months in a year',
                'evaluation_period' => 'Jan–Dec',
                'action_name' => 'Tagged as Habitual Absenteeism',
                'threshold' => 3,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            [
                'violation_type' => 'Discrepancy / Missing Timelog',
                'rule_trigger' => 'Missing required logs such as clock-in, or clock-out',
                'evaluation_period' => 'Daily / Monthly',
                'action_name' => 'Tagged as Timelogs Discrepancy / For Explanation',
                'threshold' => 1,
                'metric' => 'missing_timelogs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            [
                'violation_type' => 'Missed Break Log',
                'rule_trigger' => 'Employee missed break-out or break-in log while having time-in and time-out',
                'evaluation_period' => 'Daily / Monthly',
                'action_name' => 'Tagged as Missed Break Log / For Explanation',
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
}
