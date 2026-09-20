<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function all(): Collection
    {
        return Submission::all();
    }

    public function find(int $id): ?Submission
    {
        return Submission::find($id);
    }

    public function findOrFail(int $id): Submission
    {
        return Submission::findOrFail($id);
    }

    public function forAssignment(Assignment $assignment): Collection
    {
        return $assignment->submissions()
            ->with(['student', 'similarityReportsAsA', 'similarityReportsAsB'])
            ->get();
    }

    public function forStudent(User $student): Collection
    {
        // 'notes' is eager-loaded here (not just 'assignment') so the
        // assignments list can call Submission::hasUnseenActivity() per
        // row without an N+1 query per submission.
        return $student->submissions()->with(['assignment', 'notes'])->get();
    }

    public function create(array $data): Submission
    {
        return Submission::create($data);
    }

    public function update(Submission $submission, array $data): Submission
    {
        $submission->update($data);

        return $submission;
    }

    public function delete(Submission $submission): bool
    {
        return (bool) $submission->delete();
    }
}
