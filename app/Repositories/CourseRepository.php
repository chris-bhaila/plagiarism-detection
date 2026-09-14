<?php

namespace App\Repositories;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    public function all(): Collection
    {
        return Course::all();
    }

    public function find(int $id): ?Course
    {
        return Course::find($id);
    }

    public function findOrFail(int $id): Course
    {
        return Course::findOrFail($id);
    }

    public function forTeacher(User $teacher): Collection
    {
        return Course::where('teacher_id', $teacher->id)->get();
    }

    public function create(array $data): Course
    {
        return Course::create($data);
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        return $course;
    }

    public function delete(Course $course): bool
    {
        return (bool) $course->delete();
    }
}
