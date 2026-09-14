<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only meaningful for students, but kept as plain nullable columns on
     * users rather than a separate profile table — teachers and admins
     * simply leave them null. Semester's 1-8 range is enforced at the
     * application level, not here.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('faculty')->nullable()->after('role');
            $table->unsignedTinyInteger('semester')->nullable()->after('faculty');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['faculty', 'semester']);
        });
    }
};
