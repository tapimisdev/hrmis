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
            if (! Schema::hasColumn('violation_settings', 'metric')) {
                $table->string('metric')->nullable()->after('evaluation_period');
            }

            if (! Schema::hasColumn('violation_settings', 'monthly_threshold')) {
                $table->decimal('monthly_threshold', 8, 2)->nullable()->after('metric');
            }

            if (! Schema::hasColumn('violation_settings', 'threshold_operator')) {
                $table->string('threshold_operator', 2)->default('>=')->after('monthly_threshold');
            }

            if (! Schema::hasColumn('violation_settings', 'required_months')) {
                $table->unsignedTinyInteger('required_months')->default(1)->after('threshold_operator');
            }

            if (! Schema::hasColumn('violation_settings', 'period_type')) {
                $table->string('period_type')->default('monthly')->after('required_months');
            }

            if (! Schema::hasColumn('violation_settings', 'is_consecutive')) {
                $table->boolean('is_consecutive')->default(false)->after('period_type');
            }
        });

        $this->syncRules();
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        $columns = collect([
            'metric',
            'monthly_threshold',
            'threshold_operator',
            'required_months',
            'period_type',
            'is_consecutive',
        ])->filter(fn ($column) => Schema::hasColumn('violation_settings', $column));

        if ($columns->isEmpty()) {
            return;
        }

        Schema::table('violation_settings', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns->all());
        });
    }

    private function syncRules(): void
    {
        $now = now();

        foreach ($this->rules() as $violationType => $rule) {
            DB::table('violation_settings')
                ->where('violation_type', $violationType)
                ->update(array_merge($rule, ['updated_at' => $now]));
        }
    }

    private function rules(): array
    {
        return [
            'Tardiness / Late' => [
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness' => [
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness - Consecutive' => [
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Undertime' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Frequent Undertime' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Frequent Undertime - Consecutive' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Unauthorized Absence' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism - Consecutive' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Flag Ceremony Late' => [
                'metric' => 'flag_ceremony_lates',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Discrepancy / Missing Timelog' => [
                'metric' => 'missing_timelogs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Timelog Irregularity / Possible Falsification' => [
                'metric' => 'timelog_irregularities',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'incident',
                'is_consecutive' => false,
            ],
        ];
    }
};
