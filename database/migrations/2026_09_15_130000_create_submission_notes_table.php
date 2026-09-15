<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A running log of follow-up notes a teacher (or admin) leaves on a
     * student's submission — e.g. "please revise your citations". Teacher/
     * admin-facing only for now; no student-visible surface yet.
     */
    public function up(): void
    {
        Schema::create('submission_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_notes');
    }
};
