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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Holiday name (e.g., Christmas Day)
            $table->date('date');   // Holiday date
            $table->enum('type', [
                'regular',            // Regular Holiday
                'special_working',    // Special Working Day
                'special_non_working',// Special Non-working Day
                'company'             // Company-declared Holiday
            ])->default('regular');
            $table->boolean('is_repeating')->default(false); // repeats yearly?
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
