<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'cleared' to the status enum: an automatic result for a
     * comparison that scored below the assignment's threshold, distinct
     * from 'dismissed' (a human decision on a flagged report).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE similarity_reports MODIFY status ENUM('pending','reviewed','dismissed','confirmed','cleared') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE similarity_reports MODIFY status ENUM('pending','reviewed','dismissed','confirmed') NOT NULL DEFAULT 'pending'");
    }
};
