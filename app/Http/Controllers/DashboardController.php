<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SimilarityReport;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Analytics dashboard: most-flagged students, average scores per
     * assignment, lexical vs semantic flag breakdown. Scoped to the
     * logged-in teacher's own courses; admins see the whole institution.
     */
    public function index(Request $request): View
    {
        $courses = $this->selectableCourses();
        $selectedCourse = $courses->firstWhere('id', $request->integer('course'));
        $courseIds = $this->scopedCourseIds($selectedCourse);

        // A peer pair gets two report rows (once from each submission's own
        // check — see CheckSubmissionSimilarity), independently statusable,
        // so every count/average below would run roughly 2x real incidents
        // without this dedup. Web reports have no pair (pairKey() is null),
        // so they're kept individually via a per-id fallback key.
        $reports = SimilarityReport::with(['submissionA.assignment.course', 'submissionB.assignment'])
            ->when($courseIds !== null, fn ($query) => $query->whereHas(
                'submissionA.assignment', fn ($query) => $query->whereIn('course_id', $courseIds)
            ))
            ->get()
            ->unique(fn (SimilarityReport $r) => $r->pairKey() ?? 'web-'.$r->id);

        $flagged = $reports->filter(
            fn (SimilarityReport $r) => $r->combined_score >= ($r->submissionA->assignment->similarity_threshold ?? 0.35)
        );

        $totalSubmissions = Submission::when($courseIds !== null, fn ($query) => $query->whereHas(
            'assignment', fn ($query) => $query->whereIn('course_id', $courseIds)
        ))->count();

        $totalAssignments = Assignment::when($courseIds !== null, fn ($query) => $query->whereIn('course_id', $courseIds))->count();

        $flaggedCount = $flagged->count();
        $pendingCount = $reports->where('status', SimilarityReport::STATUS_PENDING)->count();
        $confirmedCount = $reports->where('status', SimilarityReport::STATUS_CONFIRMED)->count();
        $dismissedCount = $reports->where('status', SimilarityReport::STATUS_DISMISSED)->count();

        $studentFlaggedCount = $flagged->where('source_type', SimilarityReport::SOURCE_TYPE_SUBMISSION)->count();
        $webFlaggedCount = $flagged->where('source_type', SimilarityReport::SOURCE_TYPE_WEB)->count();

        $avgLexical = $reports->avg('lexical_score') ?? 0;
        $avgSemantic = $reports->avg('semantic_score') ?? 0;
        $avgCombined = $reports->avg('combined_score') ?? 0;

        $bars = $this->flagBreakdownByCourse($flagged);
        $topAssignments = $this->mostFlaggedAssignments($courseIds);

        return view('dashboard.index', [
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'prefix' => $request->user()->isAdmin() ? 'admin.' : '',
            'totalSubmissions' => $totalSubmissions,
            'totalAssignments' => $totalAssignments,
            'flaggedCount' => $flaggedCount,
            'flaggedPct' => $totalSubmissions > 0 ? round($flaggedCount / $totalSubmissions * 100, 1) : 0,
            'pendingCount' => $pendingCount,
            'confirmedCount' => $confirmedCount,
            'dismissedCount' => $dismissedCount,
            'confirmedPctOfFlagged' => $flaggedCount > 0 ? round($confirmedCount / $flaggedCount * 100) : 0,
            'studentFlaggedCount' => $studentFlaggedCount,
            'webFlaggedCount' => $webFlaggedCount,
            'webFlaggedPctOfFlagged' => $flaggedCount > 0 ? round($webFlaggedCount / $flaggedCount * 100) : 0,
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
            ->groupBy(fn (SimilarityReport $r) => $r->submissionA->assignment->course_id)
            ->map(function (Collection $group) {
                $course = $group->first()->submissionA->assignment->course;
                $lex = $group->filter(fn ($r) => $r->dominantSignal() === 'lexical')->count();
                $sem = $group->filter(fn ($r) => $r->dominantSignal() === 'semantic')->count();
                $both = $group->filter(fn ($r) => $r->dominantSignal() === 'both')->count();
                $total = max($group->count(), 1);

                return (object) [
                    'courseId' => $course?->id,
                    'label' => $course->name ?? 'Unknown course',
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
     * Courses the logged-in user may filter the dashboard by: a teacher's
     * own, or every course for an admin.
     *
     * @return Collection<int, Course>
     */
    protected function selectableCourses(): Collection
    {
        $user = auth()->user();

        return ($user->isTeacher() ? $user->coursesTaught() : Course::query())->orderBy('code')->get();
    }

    /**
     * The course IDs to scope the dashboard to: the chosen course if any
     * (already restricted to selectableCourses()), otherwise all of a
     * teacher's own courses, or null for "no scoping" (admins see every
     * course in the system).
     *
     * @return ?array<int, int>
     */
    protected function scopedCourseIds(?Course $selected): ?array
    {
        if ($selected) {
            return [$selected->id];
        }

        $user = auth()->user();

        return $user->isTeacher() ? $user->coursesTaught()->pluck('id')->all() : null;
    }

    /**
     * Assignments with the highest flag rate, most-flagged first.
     *
     * @param  ?array<int, int>  $courseIds
     * @return Collection<int, object>
     */
    protected function mostFlaggedAssignments(?array $courseIds): Collection
    {
        return Assignment::with('course')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('course_id', $courseIds))
            ->get()
            ->map(function (Assignment $assignment) {
                $subs = $assignment->submissions()->count();

                // See the dedup note in index() — a peer pair's two mirrored
                // rows would otherwise double this assignment's flag count
                // and skew its average score toward whichever direction
                // happened to get checked twice.
                $reports = SimilarityReport::whereHas('submissionA', fn ($q) => $q->where('assignment_id', $assignment->id))
                    ->orWhereHas('submissionB', fn ($q) => $q->where('assignment_id', $assignment->id))
                    ->get()
                    ->unique(fn (SimilarityReport $r) => $r->pairKey() ?? 'web-'.$r->id);

                $flagged = $reports->filter(fn (SimilarityReport $r) => $r->combined_score >= $assignment->similarity_threshold)->count();
                $avg = $reports->avg('combined_score') ?? 0;

                return (object) [
                    'id' => $assignment->id,
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
