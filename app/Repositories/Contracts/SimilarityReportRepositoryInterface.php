<?php

namespace App\Repositories\Contracts;

use App\Models\SimilarityReport;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Collection;

interface SimilarityReportRepositoryInterface
{
    /**
     * @return Collection<int, SimilarityReport>
     */
    public function all(): Collection;

    public function find(int $id): ?SimilarityReport;

    public function findOrFail(int $id): SimilarityReport;

    /**
     * All reports involving a given submission, on either side of the pair.
     *
     * @return Collection<int, SimilarityReport>
     */
    public function forSubmission(Submission $submission): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SimilarityReport;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SimilarityReport $report, array $data): SimilarityReport;

    public function delete(SimilarityReport $report): bool;
}
