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

        DB::table('violation_settings')->updateOrInsert(
            ['violation_type' => 'Unauthorized Absence'],
            $this->filterColumns([
                'rule_trigger' => 'Employee has at least one unauthorized absence in a month',
                'evaluation_period' => 'Count monthly',
                'action_name' => 'Unauthorized Absence',
                'threshold' => 1,
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
                'is_active' => true,
                'updated_at' => now(),
            ])
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        DB::table('violation_settings')
            ->where('violation_type', 'Unauthorized Absence')
            ->update($this->filterColumns([
                'threshold' => 3,
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'updated_at' => now(),
            ]));
    }

    private function filterColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('violation_settings', $column))
            ->all();
    }
};
