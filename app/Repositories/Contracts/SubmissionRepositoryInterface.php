<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubmissionRepositoryInterface
{
    /**
     * @return Collection<int, Submission>
     */
    public function all(): Collection;

    public function find(int $id): ?Submission;

    public function findOrFail(int $id): Submission;

    /**
     * All submissions for a given assignment.
     *
     * @return Collection<int, Submission>
     */
    public function forAssignment(Assignment $assignment): Collection;

    /**
     * All submissions made by a given student.
     *
     * @return Collection<int, Submission>
     */
    public function forStudent(User $student): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Submission;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Submission $submission, array $data): Submission;

    public function delete(Submission $submission): bool;
}
