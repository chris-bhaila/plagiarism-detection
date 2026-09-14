<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
    ) {}

    /**
     * Manage assignments for a single course.
     */
    public function forCourse(Course $course): View
    {
        $assignments = $this->assignments->forCourse($course);

        return view('teacher.assignments.index', compact('course', 'assignments'));
    }

    /**
     * Create a new assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $assignment = $this->assignments->create($validated);

        return redirect()->route('courses.assignments.index', $assignment->course_id)
            ->with('status', 'Assignment created.');
    }

    /**
     * Review submissions and similarity scores for an assignment.
     */
    public function submissions(Request $request, Assignment $assignment): View
    {
        $threshold = $assignment->similarity_threshold;

        // One row per submission: its best (highest-scoring) similarity
        // report, if it has any, drives the score badge and status shown.
        $rows = $this->submissions->forAssignment($assignment)
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

        // "Cleared" covers both a submission with no comparisons at all yet
        // (topReport is null) and one whose best comparison still scored
        // below the threshold (topReport->status is 'cleared').
        $isCleared = fn ($r) => $r->topReport === null || $r->status === SimilarityReport::STATUS_CLEARED;

        $totalCount = $rows->count();
        $flaggedCount = $rows->filter(fn ($r) => $r->score >= $threshold)->count();
        $pendingCount = $rows->filter(fn ($r) => $r->status === SimilarityReport::STATUS_PENDING)->count();
        $clearedCount = $rows->filter($isCleared)->count();
        $medianScore = $rows->pluck('score')->median() ?? 0.0;

        $filter = $request->query('filter', 'all');

        $filtered = match ($filter) {
            'flagged' => $rows->filter(fn ($r) => $r->score >= $threshold),
            'pending' => $rows->filter(fn ($r) => $r->status === SimilarityReport::STATUS_PENDING),
            'cleared' => $rows->filter($isCleared),
            default => $rows,
        };

        $rows = $filtered->sortByDesc('score')->values();

        $filters = [
            ['key' => 'all', 'label' => 'All '.$totalCount],
            ['key' => 'flagged', 'label' => 'Flagged '.$flaggedCount],
            ['key' => 'pending', 'label' => 'Pending review '.$pendingCount],
            ['key' => 'cleared', 'label' => 'Cleared '.$clearedCount],
        ];

        return view('teacher.assignments.submissions', [
            'assignment' => $assignment,
            'rows' => $rows,
            'flaggedCount' => $flaggedCount,
            'pendingCount' => $pendingCount,
            'medianScore' => $medianScore,
            'activeFilter' => $filter,
            'filters' => $filters,
        ]);
    }
}
