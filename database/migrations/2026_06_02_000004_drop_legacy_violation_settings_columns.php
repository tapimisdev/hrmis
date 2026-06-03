<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $columns = collect([
            'label',
            'warning_threshold',
            'memo_threshold',
            'other_action_threshold',
            'other_action_name',
        ])->filter(fn ($column) => Schema::hasColumn('violation_settings', $column));

        if ($columns->isEmpty()) {
            return;
        }

        Schema::table('violation_settings', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns->all());
        });
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
            if (! Schema::hasColumn('violation_settings', 'label')) {
                $table->string('label')->nullable()->after('violation_type');
            }

            if (! Schema::hasColumn('violation_settings', 'warning_threshold')) {
                $table->unsignedInteger('warning_threshold')->nullable()->after('label');
            }

            if (! Schema::hasColumn('violation_settings', 'memo_threshold')) {
                $table->unsignedInteger('memo_threshold')->nullable()->after('warning_threshold');
            }

            if (! Schema::hasColumn('violation_settings', 'other_action_threshold')) {
                $table->unsignedInteger('other_action_threshold')->nullable()->after('memo_threshold');
            }

            if (! Schema::hasColumn('violation_settings', 'other_action_name')) {
                $table->string('other_action_name')->nullable()->after('other_action_threshold');
            }
        });
    }
};
