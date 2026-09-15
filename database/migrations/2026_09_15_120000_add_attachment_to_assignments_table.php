<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An optional downloadable file (.docx only — see validation in
     * AssignmentController/AdminAssignmentController) attached at
     * assignment creation, e.g. a brief or reading. Stored on the
     * private 'local' disk and served through an authorized download
     * route rather than the public disk, so only the course's teacher,
     * admins, and enrolled students can fetch it.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('description');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
