<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->state(['role' => User::ROLE_TEACHER]),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->lexify('???')).fake()->numberBetween(100, 499),
        ];
    }
}
