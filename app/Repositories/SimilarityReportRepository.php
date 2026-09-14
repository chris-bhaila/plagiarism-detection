<?php

namespace App\Repositories;

use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SimilarityReportRepository implements SimilarityReportRepositoryInterface
{
    public function all(): Collection
    {
        return SimilarityReport::all();
    }

    public function find(int $id): ?SimilarityReport
    {
        return SimilarityReport::find($id);
    }

    public function findOrFail(int $id): SimilarityReport
    {
        return SimilarityReport::findOrFail($id);
    }

    public function forSubmission(Submission $submission): Collection
    {
        return SimilarityReport::where('submission_a_id', $submission->id)
            ->orWhere('submission_b_id', $submission->id)
            ->get();
    }

    public function create(array $data): SimilarityReport
    {
        return SimilarityReport::create($data);
    }

    public function update(SimilarityReport $report, array $data): SimilarityReport
    {
        $report->update($data);

        return $report;
    }

    public function delete(SimilarityReport $report): bool
    {
        return (bool) $report->delete();
    }
}
