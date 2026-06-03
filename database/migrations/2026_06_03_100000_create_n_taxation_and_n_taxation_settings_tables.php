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
            $table->id('UniqueID');
            $table->year('Year');
        });

        Schema::create('n_taxation_settings', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->unsignedBigInteger('n_taxation_id');
            $table->unsignedBigInteger('train_law_id');
            $table->boolean('is_longevity')->default(false);
            $table->boolean('is_hazard_pay')->default(false);
            $table->boolean('is_less_bir')->default(false);

            $table->foreign('n_taxation_id')
                ->references('UniqueID')
                ->on('n_taxation')
                ->cascadeOnDelete();

            $table->foreign('train_law_id')
                ->references('id')
                ->on('train_law');
        });

        Schema::create('n_taxation_setting_bonuses', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->unsignedBigInteger('n_taxation_setting_id');
            $table->unsignedBigInteger('government_bonus_id');

            $table->foreign('n_taxation_setting_id')
                ->references('UniqueID')
                ->on('n_taxation_settings')
                ->cascadeOnDelete();

            $table->foreign('government_bonus_id')
                ->references('id')
                ->on('government_bonus_types');
        });

        Schema::create('n_taxation_setting_others', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->unsignedBigInteger('n_taxation_setting_id');
            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_exempt_bir')->default(false);

            $table->foreign('n_taxation_setting_id')
                ->references('UniqueID')
                ->on('n_taxation_settings')
                ->cascadeOnDelete();
        });

        Schema::create('n_taxation_setting_portion', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->unsignedBigInteger('n_taxation_setting_id');
            $table->decimal('hazard_pay', 12, 2)->default(0);
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('longevity', 12, 2)->default(0);

            $table->foreign('n_taxation_setting_id')
                ->references('UniqueID')
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
        Schema::dropIfExists('n_taxation_setting_others');
        Schema::dropIfExists('n_taxation_setting_bonuses');
        Schema::dropIfExists('n_taxation_settings');
        Schema::dropIfExists('n_taxation');
    }
};
