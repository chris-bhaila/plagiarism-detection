<?php

namespace App\Http\Controllers;

use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SimilarityReportController extends Controller
{
    public function __construct(
        protected SimilarityReportRepositoryInterface $reports,
    ) {}

    /**
     * Detailed report view with highlighted overlaps between two submissions.
     */
    public function show(Request $request, SimilarityReport $similarityReport): View
    {
        $similarityReport->load([
            'submissionA.student',
            'submissionA.assignment.course',
            'submissionB.student',
        ]);

        abort_if($similarityReport->submissionA->assignment->course->teacher_id !== $request->user()->id, 403);

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
        $similarityReport->loadMissing('submissionA.assignment.course');

        abort_if($similarityReport->submissionA->assignment->course->teacher_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewed,dismissed,confirmed'],
        ]);

        $this->reports->update($similarityReport, $validated);

        return redirect()->route('similarity-reports.show', $similarityReport)
            ->with('status', 'Report marked as '.$validated['status'].'.')
            ->with('reportStatusJustChanged', [$similarityReport->id]);
    }

    /**
     * Apply one status to several reports at once — e.g. dismiss a batch
     * of low-signal matches without opening each one individually. Only
     * reports belonging to the requesting teacher's own courses.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['integer', 'exists:similarity_reports,id'],
            'status' => ['required', 'in:pending,reviewed,dismissed,confirmed'],
        ]);

        $reports = SimilarityReport::with('submissionA.assignment.course')
            ->whereIn('id', $validated['report_ids'])
            ->get();

        foreach ($reports as $report) {
            abort_if($report->submissionA->assignment->course->teacher_id !== $request->user()->id, 403);
        }

        SimilarityReport::whereIn('id', $validated['report_ids'])->update(['status' => $validated['status']]);

        $count = count($validated['report_ids']);

        return redirect()->back()
            ->with('status', "{$count} ".Str::plural('report', $count)." marked as {$validated['status']}.")
            ->with('reportStatusJustChanged', $validated['report_ids']);
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
