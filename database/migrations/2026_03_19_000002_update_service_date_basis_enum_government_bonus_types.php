<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add "current_year" option to the service_date_basis enum for existing data
        DB::statement("ALTER TABLE government_bonus_types MODIFY COLUMN service_date_basis ENUM('organization','company','current_year') NOT NULL DEFAULT 'organization'");
    }

    public function down(): void
    {
        // Revert if needed (will fail if any row uses current_year)
        DB::statement("ALTER TABLE government_bonus_types MODIFY COLUMN service_date_basis ENUM('organization','company') NOT NULL DEFAULT 'organization'");
    }
};