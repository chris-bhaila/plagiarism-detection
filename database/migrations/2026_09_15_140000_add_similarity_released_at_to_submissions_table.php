<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a teacher/admin releases a submission's similarity outcome to
     * the student it belongs to. Null means the student still only sees
     * "in progress" on their receipt page regardless of what the actual
     * report status is — release is a per-submission-side decision (like
     * SubmissionNote), not something baked into SimilarityReport, since a
     * report always spans two submissions from two different students.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('similarity_released_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('similarity_released_at');
        });
    }
};
