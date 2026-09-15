<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each faculty has exactly 8 semesters, created together when the
     * faculty is created (see AdminFacultyController).
     */
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->timestamps();

            $table->unique(['faculty_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
