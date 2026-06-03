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
        Schema::create('n_taxation', function (Blueprint $table) {
            $table->id('id');
            $table->year('Year');
            $table->timestamps();
        });

        Schema::create('n_taxation_settings', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('n_taxation_id');
            $table->unsignedBigInteger('train_law_id');

            $table->foreign('n_taxation_id')
                ->references('id')
                ->on('n_taxation')
                ->cascadeOnDelete();

            $table->foreign('train_law_id')
                ->references('id')
                ->on('train_law');
        });

        Schema::create('n_taxation_employees', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('n_taxation_id');
            $table->string('employee_no');

            $table->foreign('n_taxation_id')
                ->references('id')
                ->on('n_taxation');

            $table->timestamps();
        });


        Schema::create('n_taxation_setting_bonuses', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('n_taxation_setting_id');
            $table->unsignedBigInteger('government_bonus_id');

            $table->foreign('n_taxation_setting_id')
                ->references('id')
                ->on('n_taxation_settings')
                ->cascadeOnDelete();

            $table->foreign('government_bonus_id')
                ->references('id')
                ->on('government_bonus_types');
        });

        Schema::create('n_taxation_employee_bonus_disabled', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('n_taxation_bonus_id');

            $table->foreign('n_taxation_bonus_id')
                ->references('id')
                ->on('n_taxation_setting_bonuses');

            $table->string('employee_no');
            $table->timestamps();
        });

        Schema::create('n_taxation_setting_portion', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('n_taxation_setting_id');
            $table->decimal('hazard_pay', 12, 2)->default(0);
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('longevity', 12, 2)->default(0);

            $table->foreign('n_taxation_setting_id')
                ->references('id')
                ->on('n_taxation_settings')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('n_taxation_setting_portion');
        Schema::dropIfExists('n_taxation_employee_bonus_disabled');
        Schema::dropIfExists('n_taxation_setting_bonuses');
        Schema::dropIfExists('n_taxation_settings');
        Schema::dropIfExists('n_taxation_employees');
        Schema::dropIfExists('n_taxation');
    }
};
