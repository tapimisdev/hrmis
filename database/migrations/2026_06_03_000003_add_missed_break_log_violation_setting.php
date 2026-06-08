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
        $payload = $this->filterColumns([
            'violation_type' => 'Missed Break Log',
            'rule_trigger' => 'Employee missed break-out or break-in log while having time-in and time-out',
            'evaluation_period' => 'Daily / Monthly',
            'action_name' => 'Mark as Missed Break Log / For Explanation',
            'threshold' => 1,
            'metric' => 'missed_break_logs',
            'monthly_threshold' => 1,
            'threshold_operator' => '>=',
            'required_months' => 1,
            'period_type' => 'monthly',
            'is_consecutive' => false,
            'is_active' => true,
            'updated_at' => $now,
        ]);

        if (Schema::hasColumn('violation_settings', 'label')) {
            $payload['label'] = 'Missed Break Log';
        }

        $existing = DB::table('violation_settings')
            ->where('violation_type', 'Missed Break Log')
            ->first();

        if ($existing) {
            DB::table('violation_settings')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('violation_settings')->insert(array_merge($payload, [
            'created_at' => $now,
        ]));
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        DB::table('violation_settings')
            ->where('violation_type', 'Missed Break Log')
            ->delete();
    }

    private function filterColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('violation_settings', $column))
            ->all();
    }
};
