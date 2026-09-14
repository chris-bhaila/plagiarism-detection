<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lower the default flagging threshold from 0.4 to 0.35. Only affects
     * new assignments — existing rows keep whatever value they already have.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->float('similarity_threshold')->default(0.35)->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->float('similarity_threshold')->default(0.4)->change();
        });
    }
};
