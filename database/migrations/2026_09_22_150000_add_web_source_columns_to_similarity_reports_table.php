<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // submission_b_id -> nullable via raw SQL: a plain ->nullable()->change()
        // needs doctrine/dbal, which this project doesn't have installed.
        DB::statement('ALTER TABLE similarity_reports MODIFY submission_b_id BIGINT UNSIGNED NULL');

        Schema::table('similarity_reports', function (Blueprint $table) {
            $table->enum('source_type', ['submission', 'web'])->default('submission')->after('submission_b_id');
            $table->string('source_url')->nullable()->after('source_type');
            $table->string('source_title')->nullable()->after('source_url');
            $table->text('matched_web_passage')->nullable()->after('matched_shingles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('similarity_reports', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_url', 'source_title', 'matched_web_passage']);
        });

        DB::statement('ALTER TABLE similarity_reports MODIFY submission_b_id BIGINT UNSIGNED NOT NULL');
    }
};
