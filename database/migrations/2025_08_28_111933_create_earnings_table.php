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
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('first_term', 10, 2)->nullable();
            $table->decimal('second_term', 10, 2)->nullable();
            $table->boolean('is_taxable');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Schema::create('employee_earning', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        //     $table->foreignId('earning_id')->constrained('earnings')->onDelete('cascade');
        //     $table->string('first_term')->nullable();
        //     $table->string('second_term')->nullable();
        //     $table->enum('type', ['continuous', 'one-time'])->default('continuous');
        //     $table->boolean('is_used');
        //     $table->date('date_used');
        //     $table->date('effectivity_date')->default(now());
        //     $table->boolean('is_taxable');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('employee_earning');
        Schema::dropIfExists('earnings');
    }
};
