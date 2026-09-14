<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'student_id' => User::factory()->state(['role' => User::ROLE_STUDENT]),
            'text_content' => fake()->paragraphs(5, true),
            'submitted_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
