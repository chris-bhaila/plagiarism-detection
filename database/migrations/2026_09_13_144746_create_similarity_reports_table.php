<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('similarity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_a_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('submission_b_id')->constrained('submissions')->cascadeOnDelete();
            $table->float('lexical_score')->default(0);
            $table->float('semantic_score')->default(0);
            $table->float('combined_score')->default(0);
            $table->enum('status', ['pending', 'reviewed', 'dismissed', 'confirmed'])->default('pending');
            $table->json('matched_shingles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('similarity_reports');
    }
};
