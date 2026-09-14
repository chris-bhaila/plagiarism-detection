<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    /**
     * @return Collection<int, Course>
     */
    public function all(): Collection;

    public function find(int $id): ?Course;

    public function findOrFail(int $id): Course;

    /**
     * Courses taught by the given teacher.
     *
     * @return Collection<int, Course>
     */
    public function forTeacher(User $teacher): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course;

    public function delete(Course $course): bool;
}
