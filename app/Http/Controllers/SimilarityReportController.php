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
     *
     * Which submission renders on the left (always labeled "Source A") is
     * driven by the `for` query param, not by whichever happens to be
     * submission_a_id in this particular report row — that column is an
     * implementation detail of which submission's own check created the
     * row, not something a teacher should have to track. Without pinning
     * it, a teacher who opened this report from a specific student's row
     * and then clicked through "Other matches" would see that student
     * hop between the left and right card from one report to the next,
     * since a differently-created row could have them as either A or B.
     */
    public function show(Request $request, SimilarityReport $similarityReport): View
    {
        $similarityReport->load([
            'submissionA.student',
            'submissionA.assignment.course',
            'submissionB.student',
        ]);

        abort_if($similarityReport->submissionA->assignment->course->teacher_id !== $request->user()->id, 403);

        $focus = $this->resolveFocus($request, $similarityReport);
        $counterpart = $focus->id === $similarityReport->submission_a_id
            ? $similarityReport->submissionB
            : $similarityReport->submissionA;

        // Anchored on $focus alone (not merged with the counterpart's own
        // matches) so the list is stable across navigation: "other things
        // this same student matched with", regardless of which report you
        // arrived from.
        $otherMatches = $this->counterpartMatches($similarityReport, $focus);

        // Every pair of submissions gets a report row created from each
        // side (once while checking A, once while checking B), so without
        // deduping by pair, the same underlying match shows up twice —
        // once labeled from A's perspective, once from B's. The current
        // report's own mirror is excluded outright rather than shown as
        // an "other match", since it's the exact match already on screen.
        $currentKey = $similarityReport->pairKey();

        $otherMatches = $this->capOtherMatches(
            $otherMatches->unique('key')->reject(fn ($m) => $m->key === $currentKey)
        );

        $shingles = $similarityReport->matched_shingles ?? [];
        $matchedWords = collect($shingles)->sum(fn ($s) => str_word_count(is_array($s) ? ($s['text'] ?? '') : (string) $s));
        $totalWords = str_word_count(strip_tags($focus->text_content));

        return view('teacher.similarity-reports.show', [
            'report' => $similarityReport,
            'focus' => $focus,
            'counterpart' => $counterpart,
            'otherMatches' => $otherMatches,
            'matchedPassages' => count($shingles),
            'matchedWords' => $matchedWords,
            'totalWords' => $totalWords,
        ]);
    }

    /**
     * The submission to pin on the left ("Source A") side of the report.
     * Falls back to this row's own submission_a_id — e.g. for a bare link
     * with no `for` param, or one naming a submission not actually part
     * of this report.
     */
    protected function resolveFocus(Request $request, SimilarityReport $report): Submission
    {
        $forId = $request->integer('for');

        if ($forId && $forId === $report->submission_b_id) {
            return $report->submissionB;
        }

        return $report->submissionA;
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
        $this->syncMirrorStatus($similarityReport, $validated['status']);

        return redirect()->route('similarity-reports.show', array_filter([
                'similarityReport' => $similarityReport,
                'for' => $request->query('for'),
            ]))
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

        foreach ($reports as $report) {
            $this->syncMirrorStatus($report, $validated['status']);
        }

        $count = count($validated['report_ids']);

        return redirect()->back()
            ->with('status', "{$count} ".Str::plural('report', $count)." marked as {$validated['status']}.")
            ->with('reportStatusJustChanged', $validated['report_ids']);
    }

    /**
     * Keeps a report's mirror (the second row a pair gets — see
     * CheckSubmissionSimilarity's pairAlreadyChecked guard, which stops
     * new duplicates but doesn't touch rows that already exist) in sync,
     * so confirming/dismissing one side of a peer match doesn't silently
     * leave the other stuck on its old status.
     */
    protected function syncMirrorStatus(SimilarityReport $report, string $status): void
    {
        $key = $report->pairKey();

        if ($key === null) {
            return;
        }

        SimilarityReport::where('source_type', SimilarityReport::SOURCE_TYPE_SUBMISSION)
            ->where('id', '!=', $report->id)
            ->where(function ($query) use ($report) {
                $query->where(function ($q) use ($report) {
                    $q->where('submission_a_id', $report->submission_a_id)
                        ->where('submission_b_id', $report->submission_b_id);
                })->orWhere(function ($q) use ($report) {
                    $q->where('submission_a_id', $report->submission_b_id)
                        ->where('submission_b_id', $report->submission_a_id);
                });
            })
            ->update(['status' => $status]);
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
                if ($r->isWebSource()) {
                    return (object) [
                        'id' => $r->id,
                        'key' => 'web-'.$r->id,
                        'label' => $r->source_title ?: (parse_url($r->source_url ?? '', PHP_URL_HOST) ?: 'Web source'),
                        'score' => $r->combined_score,
                        'isWeb' => true,
                        'for' => $submission->id,
                    ];
                }

                $other = $r->submission_a_id === $submission->id
                    ? $r->submissionB()->with('student')->first()
                    : $r->submissionA()->with('student')->first();

                return (object) [
                    'id' => $r->id,
                    'key' => $r->pairKey(),
                    'label' => $other?->student->name ?? 'Unknown',
                    'score' => $r->combined_score,
                    'isWeb' => false,
                    'for' => $submission->id,
                ];
            });
    }

    /**
     * Cap "other matches" at 5, sorted by score — but a web match can be
     * real and worth surfacing even at a low score, and student-vs-student
     * matches usually dominate the top of the list since a report is
     * created per comparison direction (so the same pair can appear twice).
     * Without this, a teacher reviewing a flagged peer match would have no
     * way to tell a web source was even checked.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $matches
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function capOtherMatches(\Illuminate\Support\Collection $matches): \Illuminate\Support\Collection
    {
        $sorted = $matches->sortByDesc('score')->values();
        $top = $sorted->take(5);

        if ($top->contains('isWeb', true)) {
            return $top;
        }

        $bestWeb = $sorted->firstWhere('isWeb', true);

        if (! $bestWeb) {
            return $top;
        }

        return $top->take(4)->push($bestWeb)->sortByDesc('score')->values();
    }
}
