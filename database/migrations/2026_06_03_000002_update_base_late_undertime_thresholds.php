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

        DB::table('violation_settings')
            ->whereIn('violation_type', ['Tardiness / Late', 'Undertime'])
            ->update($this->filterColumns([
                'threshold' => 1,
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        DB::table('violation_settings')
            ->whereIn('violation_type', ['Tardiness / Late', 'Undertime'])
            ->update($this->filterColumns([
                'threshold' => 10,
                'monthly_threshold' => 10,
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
