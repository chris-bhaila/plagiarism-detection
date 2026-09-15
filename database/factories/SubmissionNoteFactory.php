<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SubmissionNote>
 */
class SubmissionNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'author_id' => User::factory()->state(['role' => User::ROLE_TEACHER]),
            'body' => fake()->sentence(12),
        ];
    }
}
