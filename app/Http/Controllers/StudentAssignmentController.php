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
     * List assignments across the authenticated student's enrolled courses.
     */
    public function index(Request $request): View
    {
        $assignments = $this->assignments->forStudent($request->user());

        return view('student.assignments.index', compact('assignments'));
    }

    /**
     * Show the submission form, or a receipt if the student already has a
     * submission for this assignment. ?revise=1 forces the form back open
     * so the student can submit a fresh attempt.
     */
    public function showSubmitForm(Request $request, Assignment $assignment): View
    {
        $submission = $this->latestSubmissionFor($request, $assignment);

        if ($submission && ! $request->boolean('revise')) {
            return view('student.assignments.submit', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]);
        }

        return view('student.assignments.submit', [
            'assignment' => $assignment,
            'submission' => null,
        ]);
    }

    /**
     * Handle a student's submission for an assignment.
     */
    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
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

    protected function latestSubmissionFor(Request $request, Assignment $assignment)
    {
        return $this->submissions->forStudent($request->user())
            ->where('assignment_id', $assignment->id)
            ->sortByDesc('submitted_at')
            ->first();
    }
}
