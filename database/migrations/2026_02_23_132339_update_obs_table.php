<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obs_applications', function (Blueprint $table) {

            if (Schema::hasColumn('obs_applications', 'created_by')) {
                $table->dropForeign(['created_by']);
            }

            if (Schema::hasColumn('obs_applications', 'updated_by')) {
                $table->dropForeign(['updated_by']);
            }

            $columnsToDrop = [
                'date_from', 'date_to', 'time_out', 'time_in',
                'destination', 'purpose', 'mode_of_transport',
                'estimated_expense', 'charge_to', 'approval_remarks',
                'approved_at', 'deleted_at', 'created_by', 'updated_by'
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('obs_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('obs_applications', function (Blueprint $table) {

            if (!Schema::hasColumn('obs_applications', 'reason')) {
                $table->text('reason')->nullable()->after('employee_no');
            }

            if (!Schema::hasColumn('obs_applications', 'name')) {
                $table->string('name')->after('application_no');
            }
        });

        Schema::create('obs_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obs_application_id')
                  ->constrained('obs_applications')
                  ->cascadeOnDelete();
            $table->enum('shift', ['morning', 'afternoon', 'wholeday']);
            $table->date('date');
            $table->boolean('isActive')->default(true);
            $table->index(['obs_application_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obs_dates');

        Schema::table('obs_applications', function (Blueprint $table) {

            if (Schema::hasColumn('obs_applications', 'reason')) {
                $table->dropColumn('reason');
            }

            if (Schema::hasColumn('obs_applications', 'name')) {
                $table->dropColumn('name');
            }

            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->time('time_out')->nullable();
            $table->time('time_in')->nullable();
            $table->string('destination')->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('mode_of_transport')->nullable();
            $table->decimal('estimated_expense', 12, 2)->default(0);
            $table->string('charge_to')->nullable();
            $table->longText('approval_remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};