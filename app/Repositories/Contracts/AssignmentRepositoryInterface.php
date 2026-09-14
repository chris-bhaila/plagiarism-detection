<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AssignmentRepositoryInterface
{
    /**
     * @return Collection<int, Assignment>
     */
    public function all(): Collection;

    public function find(int $id): ?Assignment;

    public function findOrFail(int $id): Assignment;

    /**
     * Assignments belonging to a single course.
     *
     * @return Collection<int, Assignment>
     */
    public function forCourse(Course $course): Collection;

    /**
     * Assignments across all courses a student is enrolled in.
     *
     * @return Collection<int, Assignment>
     */
    public function forStudent(User $student): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Assignment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Assignment $assignment, array $data): Assignment;

    public function delete(Assignment $assignment): bool;
}
