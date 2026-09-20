<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the student last opened their own submission's receipt page.
     * Null means never. Used to work out whether a note or a similarity
     * release happened since the student last looked — see
     * Submission::hasUnseenActivity() — so the assignments list can show
     * a "New" indicator instead of the student having to reopen every
     * assignment to notice.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('similarity_released_at');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('viewed_at');
        });
    }
};
