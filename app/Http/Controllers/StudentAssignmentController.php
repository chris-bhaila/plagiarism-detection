<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\SimilarityReportRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use App\Services\SimilarityCheckClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentAssignmentController extends Controller
{
    public function __construct(
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
        protected SimilarityReportRepositoryInterface $similarityReports,
        protected SimilarityCheckClient $similarityCheckClient,
    ) {}

    /**
     * List assignments across the authenticated student's enrolled courses,
     * each annotated with the student's own submission (if any) so the
     * list can show Submitted / Not submitted / Overdue.
     */
    public function index(Request $request): View
    {
        $student = $request->user();
        $assignments = $this->assignments->forStudent($student);

        $latestByAssignment = $this->submissions->forStudent($student)
            ->sortByDesc('submitted_at')
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $undated = now()->addCentury();

        $rows = $assignments->map(fn (Assignment $assignment) => (object) [
            'assignment' => $assignment,
            'submission' => $latestByAssignment->get($assignment->id),
        ])->sortBy(fn ($row) => $row->assignment->due_date ?? $undated)->values();

        return view('student.assignments.index', ['rows' => $rows]);
    }

    /**
     * Show the submission form, or a receipt (plus any earlier attempts)
     * if the student already has a submission for this assignment.
     * ?revise=1 forces the form back open so the student can submit a
     * fresh attempt.
     */
    public function showSubmitForm(Request $request, Assignment $assignment): View
    {
        $this->authorizeEnrollment($request, $assignment);

        $submissions = $this->submissionsForAssignment($request, $assignment);
        $latest = $submissions->first();

        if ($latest && ! $request->boolean('revise')) {
            return view('student.assignments.submit', [
                'assignment' => $assignment,
                'submission' => $latest,
                'previousSubmissions' => $submissions->slice(1)->values(),
            ]);
        }

        return view('student.assignments.submit', [
            'assignment' => $assignment,
            'submission' => null,
            'previousSubmissions' => collect(),
        ]);
    }

    /**
     * Handle a student's submission for an assignment.
     */
    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeEnrollment($request, $assignment);

        $validated = $request->validate([
            'text_content' => ['required', 'string'],
        ]);

        $submission = $this->submissions->create([
            'assignment_id' => $assignment->id,
            'student_id' => $request->user()->id,
            'text_content' => $validated['text_content'],
            'submitted_at' => now(),
        ]);

        // TODO: dispatch this to a queued job once submissions/checks get big
        // enough that doing it inline noticeably delays the response.
        $results = $this->similarityCheckClient->checkSubmission(
            $submission->id,
            $submission->text_content,
            $assignment->id,
        );

        foreach ($results as $result) {
            $this->similarityReports->create([
                'submission_a_id' => $submission->id,
                'submission_b_id' => $result['compared_submission_id'],
                'lexical_score' => $result['lexical_score'],
                'semantic_score' => $result['semantic_score'],
                'combined_score' => $result['combined_score'],
                'matched_shingles' => $result['matched_shingles'] ?? null,
                'status' => $result['combined_score'] >= $assignment->similarity_threshold
                    ? SimilarityReport::STATUS_PENDING
                    : SimilarityReport::STATUS_CLEARED,
            ]);
        }

        return redirect()->route('assignments.submit.show', $assignment)
            ->with('status', 'Submission received.');
    }

    /**
     * A student may only open or submit to an assignment belonging to a
     * course in their own semester — otherwise 404, matching how the rest
     * of the app treats "not in scope" (see CourseController::showStudent).
     */
    protected function authorizeEnrollment(Request $request, Assignment $assignment): void
    {
        abort_unless($assignment->course->semester_id === $request->user()->semester_id, 404);
    }

    /**
     * Every submission the student has made for this assignment, newest
     * first — not just the latest, so the receipt page can show a history
     * of past attempts.
     *
     * @return Collection<int, \App\Models\Submission>
     */
    protected function submissionsForAssignment(Request $request, Assignment $assignment): Collection
    {
        return $this->submissions->forStudent($request->user())
            ->where('assignment_id', $assignment->id)
            ->sortByDesc('submitted_at')
            ->values();
    }
}
