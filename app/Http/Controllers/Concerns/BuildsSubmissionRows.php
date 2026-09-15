<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Shared by AssignmentController (teacher) and AdminAssignmentController —
 * pure scoring/shaping logic, not permission logic, so like
 * StoresAssignmentAttachment this is fine to share even though the two
 * controllers otherwise stay intentionally duplicated (see CLAUDE.md).
 * Used by both the submissions-review page and the CSV export.
 */
trait BuildsSubmissionRows
{
    /**
     * One row per submission to $assignment: its best (highest-scoring)
     * similarity report, if any, drives the score/status shown.
     *
     * @return Collection<int, object>
     */
    protected function buildSubmissionRows(SubmissionRepositoryInterface $submissions, Assignment $assignment): Collection
    {
        $threshold = $assignment->similarity_threshold;

        return $submissions->forAssignment($assignment)
            ->map(function ($submission) use ($threshold) {
                $top = $submission->similarityReports()->first();
                $score = $top->combined_score ?? 0.0;

                return (object) [
                    'submission' => $submission,
                    'topReport' => $top,
                    'score' => $score,
                    'lexical' => $top->lexical_score ?? 0.0,
                    'semantic' => $top->semantic_score ?? 0.0,
                    'status' => $top->status ?? null,
                    'matchCount' => $submission->similarityReports()->count(),
                    'wordCount' => str_word_count(strip_tags($submission->text_content)),
                    'band' => SimilarityReport::scoreBand($score, $threshold),
                ];
            });
    }
}
