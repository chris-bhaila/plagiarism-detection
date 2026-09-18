<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsStudentAssignmentRows;
use App\Jobs\CheckSubmissionSimilarity;
use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentAssignmentController extends Controller
{
    use BuildsStudentAssignmentRows;

    public function __construct(
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
    ) {}

    /**
     * List assignments across the authenticated student's enrolled courses,
     * each annotated with the student's own submission (if any) so the
     * list can show Submitted / Not submitted / Overdue, plus a "New"
     * indicator when a note or similarity release happened since the
     * student last opened that assignment. Filterable by title/course-code
     * search text, same pattern as the teacher/admin lists.
     */
    public function index(Request $request): View
    {
        $student = $request->user();
        $search = $request->query('search');
        $assignments = $this->assignments->forStudent($student, $search);

        $rows = $this->buildStudentAssignmentRows($this->submissions, $student, $assignments);

        return view('student.assignments.index', ['rows' => $rows, 'search' => $search]);
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
            $latest->markViewed();

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

        CheckSubmissionSimilarity::dispatch($submission);

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
