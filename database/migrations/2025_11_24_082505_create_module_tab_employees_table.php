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
        Schema::create('module_tab_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_tab_id')->constrained('module_tabs')->onDelete('cascade');
            $table->string('employee_no');
            $table->year('year');
            $table->string('month');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_tab_employees');
    }
};
