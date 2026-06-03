<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        Schema::table('violation_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('violation_settings', 'rule_trigger')) {
                $table->text('rule_trigger')->nullable()->after('violation_type');
            }

            if (! Schema::hasColumn('violation_settings', 'evaluation_period')) {
                $table->string('evaluation_period')->nullable()->after('rule_trigger');
            }
        });

        $now = now();
        $rules = [
            ['Tardiness / Late', 'Employee has 10 or more lates in a month', 'Count monthly', 'Mark as Habitual Tardiness Candidate', 10],
            ['Habitual Tardiness', 'Employee has 10 or more lates per month for at least 2 months in one semester', 'Jan–Jun or Jul–Dec', 'Habitual Tardiness', 2],
            ['Habitual Tardiness - Consecutive', 'Employee has 10 or more lates for 2 consecutive months in a year', 'Jan–Dec', 'Habitual Tardiness', 2],
            ['Undertime', 'Employee has 10 or more undertimes in a month', 'Count monthly', 'Mark as Frequent Undertime Candidate', 10],
            ['Frequent Undertime', 'Employee has 10 or more undertimes per month for at least 2 months in one semester', 'Jan–Jun or Jul–Dec', 'Frequent Undertime', 2],
            ['Frequent Undertime - Consecutive', 'Employee has 10 or more undertimes for 2 consecutive months in a year', 'Jan–Dec', 'Frequent Undertime', 2],
            ['Unauthorized Absence', 'Employee has more than 2.5 unauthorized absences in a month', 'Count monthly', 'Mark as Habitual Absenteeism Candidate', 3],
            ['Habitual Absenteeism', 'Employee has unauthorized absences exceeding 2.5 days monthly for at least 3 months in a semester', 'Jan–Jun or Jul–Dec', 'Habitual Absenteeism', 3],
            ['Habitual Absenteeism - Consecutive', 'Employee has unauthorized absences exceeding 2.5 days monthly for 3 consecutive months in a year', 'Jan–Dec', 'Habitual Absenteeism', 3],
            ['Flag Ceremony Late', 'On flag ceremony day, employee time-in is after 8:00 AM', 'Count monthly', 'Mark as Late due to Flag Ceremony Policy', 1],
            ['Discrepancy / Missing Timelog', 'Missing required logs such as clock-in, break-out, break-in, or clock-out', 'Daily / Monthly', 'Mark as Incomplete Timelog / For Explanation', 1],
            ['Timelog Irregularity / Possible Falsification', 'Manual, suspicious, altered, or inconsistent timelog record', 'Per incident', 'Mark as For HR Review / Possible Timelog Irregularity', 1],
        ];

        DB::table('violation_settings')
            ->whereIn('violation_type', ['UT', 'Absences', 'Incomplete Timelogs'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        foreach ($rules as [$type, $trigger, $period, $action, $threshold]) {
            $existing = DB::table('violation_settings')->where('violation_type', $type)->first();

            $payload = [
                'rule_trigger' => $trigger,
                'evaluation_period' => $period,
                'action_name' => $action,
                'threshold' => $threshold,
                'is_active' => true,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('violation_settings')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('violation_settings')->insert(array_merge($payload, [
                'violation_type' => $type,
                'created_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        DB::table('violation_settings')
            ->whereIn('violation_type', [
                'Tardiness / Late',
                'Habitual Tardiness',
                'Habitual Tardiness - Consecutive',
                'Undertime',
                'Frequent Undertime',
                'Frequent Undertime - Consecutive',
                'Unauthorized Absence',
                'Habitual Absenteeism',
                'Habitual Absenteeism - Consecutive',
                'Flag Ceremony Late',
                'Discrepancy / Missing Timelog',
                'Timelog Irregularity / Possible Falsification',
            ])
            ->delete();

        Schema::table('violation_settings', function (Blueprint $table) {
            if (Schema::hasColumn('violation_settings', 'rule_trigger')) {
                $table->dropColumn('rule_trigger');
            }

            if (Schema::hasColumn('violation_settings', 'evaluation_period')) {
                $table->dropColumn('evaluation_period');
            }
        });
    }
};
