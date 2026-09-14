<?php

namespace Database\Factories;

use App\Models\SimilarityReport;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimilarityReport>
 */
class SimilarityReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lexical = fake()->randomFloat(2, 0, 1);
        $semantic = fake()->randomFloat(2, 0, 1);

        return [
            'submission_a_id' => Submission::factory(),
            'submission_b_id' => Submission::factory(),
            'lexical_score' => $lexical,
            'semantic_score' => $semantic,
            'combined_score' => round(($lexical + $semantic) / 2, 2),
            'status' => fake()->randomElement([
                SimilarityReport::STATUS_PENDING,
                SimilarityReport::STATUS_REVIEWED,
                SimilarityReport::STATUS_DISMISSED,
                SimilarityReport::STATUS_CONFIRMED,
            ]),
            'matched_shingles' => null,
        ];
    }
}
