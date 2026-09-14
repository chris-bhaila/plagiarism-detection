<?php

namespace App\Http\Controllers;

use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimilarityReportController extends Controller
{
    public function __construct(
        protected SimilarityReportRepositoryInterface $reports,
    ) {}

    /**
     * Detailed report view with highlighted overlaps between two submissions.
     */
    public function show(SimilarityReport $similarityReport): View
    {
        $similarityReport->load([
            'submissionA.student',
            'submissionA.assignment.course',
            'submissionB.student',
        ]);

        $otherMatches = $this->counterpartMatches($similarityReport, $similarityReport->submissionA)
            ->merge($this->counterpartMatches($similarityReport, $similarityReport->submissionB))
            ->sortByDesc('score')
            ->take(5)
            ->values();

        $shingles = $similarityReport->matched_shingles ?? [];
        $matchedWords = collect($shingles)->sum(fn (array $s) => str_word_count($s['text'] ?? ''));
        $totalWords = str_word_count(strip_tags($similarityReport->submissionA->text_content));

        return view('teacher.similarity-reports.show', [
            'report' => $similarityReport,
            'otherMatches' => $otherMatches,
            'matchedPassages' => count($shingles),
            'matchedWords' => $matchedWords,
            'totalWords' => $totalWords,
        ]);
    }

    /**
     * Update a report's review status (confirm / mark reviewed / dismiss).
     */
    public function updateStatus(Request $request, SimilarityReport $similarityReport): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewed,dismissed,confirmed'],
        ]);

        $this->reports->update($similarityReport, $validated);

        return redirect()->route('similarity-reports.show', $similarityReport)
            ->with('status', 'Report marked as '.$validated['status'].'.');
    }

    /**
     * The other similarity matches involving $submission (excluding the
     * current report), labeled by whoever is on the other side of each.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function counterpartMatches(SimilarityReport $current, Submission $submission): \Illuminate\Support\Collection
    {
        return $this->reports->forSubmission($submission)
            ->reject(fn (SimilarityReport $r) => $r->id === $current->id)
            ->map(function (SimilarityReport $r) use ($submission) {
                $other = $r->submission_a_id === $submission->id
                    ? $r->submissionB()->with('student')->first()
                    : $r->submissionA()->with('student')->first();

                return (object) [
                    'label' => $other?->student->name ?? 'Unknown',
                    'score' => $r->combined_score,
                ];
            });
    }
}
