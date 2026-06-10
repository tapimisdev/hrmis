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
        Schema::create('train_law_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('train_law_id')
                ->constrained('train_law')
                ->onDelete('cascade');

            // Income bracket range
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to', 15, 2)->nullable(); // NULL for last bracket (above limit)

            // TRAIN Law computation fields
            $table->decimal('fixed_tax', 15, 2)->default(0);     // Base tax amount
            $table->decimal('tax_rate', 5, 2)->default(0);       // Percentage (ex: 15, 20, 25)
            $table->decimal('excess_over', 15, 2)->default(0);   // Amount where % starts
            
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('train_law_items');
    }
};
