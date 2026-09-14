<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Analytics dashboard: most-flagged students, average scores per
     * assignment, lexical vs semantic flag breakdown.
     */
    public function index(): View
    {
        $reports = SimilarityReport::with(['submissionA.assignment.course', 'submissionB.assignment'])->get();

        $flagged = $reports->filter(
            fn (SimilarityReport $r) => $r->combined_score >= ($r->submissionA->assignment->similarity_threshold ?? 0.35)
        );

        $totalSubmissions = Submission::count();
        $totalAssignments = Assignment::count();
        $flaggedCount = $flagged->count();
        $pendingCount = $reports->where('status', SimilarityReport::STATUS_PENDING)->count();
        $confirmedCount = $reports->where('status', SimilarityReport::STATUS_CONFIRMED)->count();
        $dismissedCount = $reports->where('status', SimilarityReport::STATUS_DISMISSED)->count();

        $avgLexical = $reports->avg('lexical_score') ?? 0;
        $avgSemantic = $reports->avg('semantic_score') ?? 0;
        $avgCombined = $reports->avg('combined_score') ?? 0;

        $bars = $this->flagBreakdownByCourse($flagged);
        $topAssignments = $this->mostFlaggedAssignments();

        return view('dashboard.index', [
            'totalSubmissions' => $totalSubmissions,
            'totalAssignments' => $totalAssignments,
            'flaggedCount' => $flaggedCount,
            'flaggedPct' => $totalSubmissions > 0 ? round($flaggedCount / $totalSubmissions * 100, 1) : 0,
            'pendingCount' => $pendingCount,
            'confirmedCount' => $confirmedCount,
            'dismissedCount' => $dismissedCount,
            'confirmedPctOfFlagged' => $flaggedCount > 0 ? round($confirmedCount / $flaggedCount * 100) : 0,
            'avgLexicalPct' => round($avgLexical * 100),
            'avgSemanticPct' => round($avgSemantic * 100),
            'avgCombinedPct' => round($avgCombined * 100),
            'bars' => $bars,
            'topAssignments' => $topAssignments,
        ]);
    }

    /**
     * For each course, how many flagged reports were lexical-driven,
     * semantic-driven, or roughly both.
     *
     * @param  Collection<int, SimilarityReport>  $flagged
     * @return Collection<int, object>
     */
    protected function flagBreakdownByCourse(Collection $flagged): Collection
    {
        return $flagged
            ->groupBy(fn (SimilarityReport $r) => $r->submissionA->assignment->course->name ?? 'Unknown course')
            ->map(function (Collection $group, string $courseName) {
                $lex = $group->filter(fn ($r) => $r->dominantSignal() === 'lexical')->count();
                $sem = $group->filter(fn ($r) => $r->dominantSignal() === 'semantic')->count();
                $both = $group->filter(fn ($r) => $r->dominantSignal() === 'both')->count();
                $total = max($group->count(), 1);

                return (object) [
                    'label' => $courseName,
                    'total' => $group->count(),
                    'lexPct' => round($lex / $total * 100),
                    'semPct' => round($sem / $total * 100),
                    'bothPct' => round($both / $total * 100),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Assignments with the highest flag rate, most-flagged first.
     *
     * @return Collection<int, object>
     */
    protected function mostFlaggedAssignments(): Collection
    {
        return Assignment::with('course')
            ->get()
            ->map(function (Assignment $assignment) {
                $subs = $assignment->submissions()->count();

                $reports = SimilarityReport::whereHas('submissionA', fn ($q) => $q->where('assignment_id', $assignment->id))
                    ->orWhereHas('submissionB', fn ($q) => $q->where('assignment_id', $assignment->id))
                    ->get();

                $flagged = $reports->filter(fn (SimilarityReport $r) => $r->combined_score >= $assignment->similarity_threshold)->count();
                $avg = $reports->avg('combined_score') ?? 0;

                return (object) [
                    'name' => $assignment->title,
                    'course' => $assignment->course->code ?? '',
                    'subs' => $subs,
                    'flagged' => $flagged,
                    'avg' => round($avg * 100),
                    'band' => SimilarityReport::scoreBand($avg, $assignment->similarity_threshold),
                ];
            })
            ->sortByDesc('flagged')
            ->take(6)
            ->values();
    }
}
