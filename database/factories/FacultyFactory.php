<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['BCA', 'BIM', 'BBM', 'BBA', 'B.Sc.']),
        ];
    }

    /**
     * Create this faculty's 8 fixed semesters alongside it.
     */
    public function withSemesters(): static
    {
        return $this->afterCreating(function (Faculty $faculty) {
            for ($number = 1; $number <= 8; $number++) {
                $faculty->semesters()->create(['number' => $number]);
            }
        });
    }
}
