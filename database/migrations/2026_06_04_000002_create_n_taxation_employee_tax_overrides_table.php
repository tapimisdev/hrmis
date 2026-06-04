<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n_taxation_employee_tax_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('n_taxation_id');
            $table->string('employee_no');
            $table->string('tax_type');
            $table->unsignedTinyInteger('month_number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('n_taxation_id')
                ->references('id')
                ->on('n_taxation')
                ->cascadeOnDelete();

            $table->unique(
                ['n_taxation_id', 'employee_no', 'tax_type', 'month_number'],
                'n_taxation_employee_tax_override_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n_taxation_employee_tax_overrides');
    }
};
