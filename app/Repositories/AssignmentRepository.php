<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRepository implements AssignmentRepositoryInterface
{
    public function all(): Collection
    {
        return Assignment::all();
    }

    public function find(int $id): ?Assignment
    {
        return Assignment::find($id);
    }

    public function findOrFail(int $id): Assignment
    {
        return Assignment::findOrFail($id);
    }

    public function forCourse(Course $course): Collection
    {
        return $course->assignments()->get();
    }

    public function forStudent(User $student): Collection
    {
        // NOTE: there is no course enrollment table yet, so this currently
        // returns all assignments. Once enrollment is modeled, scope this
        // to the courses the student is actually enrolled in.
        return Assignment::with('course')->get();
    }

    public function create(array $data): Assignment
    {
        return Assignment::create($data);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->update($data);

        return $assignment;
    }

    public function delete(Assignment $assignment): bool
    {
        return (bool) $assignment->delete();
    }
}
