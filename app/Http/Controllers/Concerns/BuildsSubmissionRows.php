<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\Request;
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
                $reports = $submission->similarityReports();
                $top = $reports->first();
                $score = $top->combined_score ?? 0.0;

                return (object) [
                    'submission' => $submission,
                    'topReport' => $top,
                    'score' => $score,
                    'lexical' => $top->lexical_score ?? 0.0,
                    'semantic' => $top->semantic_score ?? 0.0,
                    'status' => $top->status ?? null,
                    // A peer pair gets two report rows (once from each
                    // submission's own check — see CheckSubmissionSimilarity),
                    // so a plain count() overstates "matched sources" by
                    // roughly 2x for any assignment with real peer matches.
                    // Web matches don't have this duplication (no pairKey).
                    'matchCount' => $reports->filter(fn (SimilarityReport $r) => $r->isWebSource())->count()
                        + $reports->reject(fn (SimilarityReport $r) => $r->isWebSource())->map->pairKey()->unique()->count(),
                    // Not just topReport->isWebSource(): a submission can have a
                    // lower-scoring web match sitting behind a higher-scoring
                    // peer match, and a teacher should still be able to tell a
                    // web source was checked at all.
                    'hasWebMatch' => $reports->contains(fn (SimilarityReport $r) => $r->isWebSource()),
                    'wordCount' => str_word_count(strip_tags($submission->text_content)),
                    'band' => SimilarityReport::scoreBand($score, $threshold),
                ];
            });
    }

    /**
     * Apply the submissions page's ?search= (student name/email) and
     * ?sort= (score|name|submitted|words, default score, highest first).
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function searchAndSortRows(Collection $rows, Request $request): Collection
    {
        $search = mb_strtolower(trim((string) $request->query('search', '')));

        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => str_contains(
                mb_strtolower($r->submission->student->name.' '.$r->submission->student->email),
                $search,
            ));
        }

        $sorted = match ($request->query('sort')) {
            'name' => $rows->sortBy(fn ($r) => mb_strtolower($r->submission->student->name)),
            'submitted' => $rows->sortByDesc(fn ($r) => $r->submission->submitted_at),
            'words' => $rows->sortByDesc('wordCount'),
            default => $rows->sortByDesc('score'),
        };

        return $sorted->values();
    }
}
