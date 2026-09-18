<?php

namespace App\Http\Controllers;

use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSimilarityReportController extends Controller
{
    public function __construct(
        protected SimilarityReportRepositoryInterface $reports,
    ) {}

    /**
     * Mirrors SimilarityReportController::show() for admins, who have the
     * same review capability as a course's teacher.
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

        return view('admin.similarity-reports.show', [
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

        return redirect()->route('admin.similarity-reports.show', $similarityReport)
            ->with('status', 'Report marked as '.$validated['status'].'.')
            ->with('reportStatusJustChanged', [$similarityReport->id]);
    }

    /**
     * Mirrors SimilarityReportController::bulkUpdateStatus() for admins —
     * no ownership check, since admin can review any course's reports.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['integer', 'exists:similarity_reports,id'],
            'status' => ['required', 'in:pending,reviewed,dismissed,confirmed'],
        ]);

        SimilarityReport::whereIn('id', $validated['report_ids'])->update(['status' => $validated['status']]);

        $count = count($validated['report_ids']);

        return redirect()->back()
            ->with('status', "{$count} ".Str::plural('report', $count)." marked as {$validated['status']}.")
            ->with('reportStatusJustChanged', $validated['report_ids']);
    }

    /**
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
