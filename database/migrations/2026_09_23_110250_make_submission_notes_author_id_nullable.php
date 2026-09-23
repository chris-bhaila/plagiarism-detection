<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A submission's own notes are the record a teacher/admin left about a
     * student; the account that wrote them shouldn't decide whether that
     * record survives. author_id was cascadeOnDelete, so deleting any
     * teacher or admin account silently wiped every note they'd ever left,
     * across every course they'd taught — not just their own data. Switches
     * to SET NULL (raw SQL: a plain ->nullable()->change() needs
     * doctrine/dbal, which this project doesn't have installed — same
     * reason 2026_09_22_150000_add_web_source_columns... used raw SQL).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE submission_notes DROP FOREIGN KEY submission_notes_author_id_foreign');
        DB::statement('ALTER TABLE submission_notes MODIFY author_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE submission_notes ADD CONSTRAINT submission_notes_author_id_foreign FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE submission_notes DROP FOREIGN KEY submission_notes_author_id_foreign');
        DB::statement('ALTER TABLE submission_notes MODIFY author_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE submission_notes ADD CONSTRAINT submission_notes_author_id_foreign FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
    }
};
