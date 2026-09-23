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
     * same review capability as a course's teacher. See there for why the
     * `for` param pins a submission to the left/"Source A" card instead of
     * letting it drift with whichever report row happens to be open.
     */
    public function show(Request $request, SimilarityReport $similarityReport): View
    {
        $similarityReport->load([
            'submissionA.student',
            'submissionA.assignment.course',
            'submissionB.student',
        ]);

        $focus = $this->resolveFocus($request, $similarityReport);
        $counterpart = $focus->id === $similarityReport->submission_a_id
            ? $similarityReport->submissionB
            : $similarityReport->submissionA;

        $otherMatches = $this->counterpartMatches($similarityReport, $focus);

        // See SimilarityReportController::show() for why the dedup-by-pair
        // and current-pair exclusion are both needed here.
        $currentKey = $similarityReport->pairKey();

        $otherMatches = $this->capOtherMatches(
            $otherMatches->unique('key')->reject(fn ($m) => $m->key === $currentKey)
        );

        $shingles = $similarityReport->matched_shingles ?? [];
        $matchedWords = collect($shingles)->sum(fn ($s) => str_word_count(is_array($s) ? ($s['text'] ?? '') : (string) $s));
        $totalWords = str_word_count(strip_tags($focus->text_content));

        return view('admin.similarity-reports.show', [
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
     * Mirrors SimilarityReportController::resolveFocus().
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
        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewed,dismissed,confirmed'],
        ]);

        $this->reports->update($similarityReport, $validated);
        $this->syncMirrorStatus($similarityReport, $validated['status']);

        return redirect()->route('admin.similarity-reports.show', array_filter([
                'similarityReport' => $similarityReport,
                'for' => $request->query('for'),
            ]))
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

        $reports = SimilarityReport::whereIn('id', $validated['report_ids'])->get();

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
     * Mirrors SimilarityReportController::syncMirrorStatus().
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
     * Mirrors SimilarityReportController::capOtherMatches() — see there for
     * why a web match is guaranteed a slot instead of being crowded out by
     * higher-scoring (and often mirrored-direction) peer matches.
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
