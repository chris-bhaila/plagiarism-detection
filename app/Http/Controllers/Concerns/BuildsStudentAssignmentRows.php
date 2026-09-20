<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assignment;
use App\Models\User;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Shared by StudentAssignmentController, StudentCourseController, and
 * StudentDashboardController — pure shaping logic (pairing each assignment
 * with the student's own latest submission, if any, sorted by due date),
 * not permission logic, same rationale as BuildsSubmissionRows.
 */
trait BuildsStudentAssignmentRows
{
    /**
     * @param  Collection<int, Assignment>  $assignments
     * @return Collection<int, object>
     */
    protected function buildStudentAssignmentRows(SubmissionRepositoryInterface $submissions, User $student, Collection $assignments): Collection
    {
        $latestByAssignment = $submissions->forStudent($student)
            ->sortByDesc('submitted_at')
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $undated = now()->addCentury();

        return $assignments->map(fn (Assignment $assignment) => (object) [
            'assignment' => $assignment,
            'submission' => $latestByAssignment->get($assignment->id),
        ])->sortBy(fn ($row) => $row->assignment->due_date ?? $undated)->values();
    }
}
