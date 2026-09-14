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

    public function searchAvailableStudents(Course $course, array $filters): Collection
    {
        $enrolledIds = $course->students()->pluck('users.id');

        return User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereNotIn('id', $enrolledIds)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['faculty'] ?? null, fn ($query, string $faculty) => $query->where('faculty', $faculty))
            ->when($filters['semester'] ?? null, fn ($query, int $semester) => $query->where('semester', $semester))
            ->orderBy('name')
            ->get();
    }

    public function enrollStudents(Course $course, array $studentIds): void
    {
        $course->students()->syncWithoutDetaching($studentIds);
    }
}
