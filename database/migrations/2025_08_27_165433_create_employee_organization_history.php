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
        Schema::create('employee_organization', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->foreignId('division_id')
                ->constrained('divisions')
                ->onDelete('restrict');
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('restrict');
            $table->foreignId('employment_type_id')
                ->constrained('employment_types')
                ->onDelete('restrict');
            $table->foreignId('position_id')
                ->constrained('positions')
                ->onDelete('restrict');
            $table->date('effectivity_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_organization');
    }
};
