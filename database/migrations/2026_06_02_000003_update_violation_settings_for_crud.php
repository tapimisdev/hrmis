<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        $this->dropViolationTypeUniqueIndex();

        Schema::table('violation_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('violation_settings', 'action_name')) {
                $table->string('action_name')->nullable()->after('violation_type');
            }

            if (! Schema::hasColumn('violation_settings', 'threshold')) {
                $table->unsignedInteger('threshold')->nullable()->after('action_name');
            }

            if (! Schema::hasColumn('violation_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('threshold');
            }
        });

        if (Schema::hasColumn('violation_settings', 'warning_threshold')) {
            $this->migrateOldThresholdRows();
        }

        DB::table('violation_settings')
            ->whereNull('action_name')
            ->update(['action_name' => 'Warning']);

        DB::table('violation_settings')
            ->whereNull('threshold')
            ->update(['threshold' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        Schema::table('violation_settings', function (Blueprint $table) {
            if (Schema::hasColumn('violation_settings', 'action_name')) {
                $table->dropColumn('action_name');
            }

            if (Schema::hasColumn('violation_settings', 'threshold')) {
                $table->dropColumn('threshold');
            }

            if (Schema::hasColumn('violation_settings', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    private function dropViolationTypeUniqueIndex(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM violation_settings WHERE Non_unique = 0 AND Column_name = 'violation_type'");

        foreach ($indexes as $index) {
            DB::statement('ALTER TABLE violation_settings DROP INDEX `' . $index->Key_name . '`');
        }
    }

    private function migrateOldThresholdRows(): void
    {
        $rows = DB::table('violation_settings')->get();

        foreach ($rows as $row) {
            $rules = [
                ['action_name' => 'Warning', 'threshold' => $row->warning_threshold ?? null],
                ['action_name' => 'Memo', 'threshold' => $row->memo_threshold ?? null],
                ['action_name' => $row->other_action_name ?? null, 'threshold' => $row->other_action_threshold ?? null],
            ];

            $firstRule = collect($rules)->first(fn ($rule) => filled($rule['action_name']) && filled($rule['threshold']));

            if ($firstRule) {
                DB::table('violation_settings')
                    ->where('id', $row->id)
                    ->update([
                        'action_name' => $firstRule['action_name'],
                        'threshold' => $firstRule['threshold'],
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
            }

            collect($rules)
                ->filter(fn ($rule) => filled($rule['action_name']) && filled($rule['threshold']))
                ->skip($firstRule ? 1 : 0)
                ->each(function ($rule) use ($row) {
                    DB::table('violation_settings')->insert([
                        'violation_type' => $row->violation_type,
                        'action_name' => $rule['action_name'],
                        'threshold' => $rule['threshold'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }
};
