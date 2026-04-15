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
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false)->after('message');
            }

            if (!Schema::hasColumn('feedbacks', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('is_anonymous');
            }

            if (!Schema::hasColumn('feedbacks', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }

            if (!Schema::hasColumn('feedbacks', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }

            if (!Schema::hasColumn('feedbacks', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $columns = [
                'is_anonymous',
                'attachment_path',
                'attachment_name',
                'attachment_mime',
                'attachment_size',
            ];

            $existingColumns = array_filter($columns, fn ($column) => Schema::hasColumn('feedbacks', $column));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
