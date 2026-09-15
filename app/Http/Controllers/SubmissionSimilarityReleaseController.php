<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionSimilarityReleaseController extends Controller
{
    /**
     * Toggle whether this submission's similarity outcome is visible to
     * the student — only the course's own teacher may. Mirrors
     * SubmissionNoteController's per-submission-side ownership check.
     */
    public function update(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->assignment->course->teacher_id !== $request->user()->id, 403);

        $submission->similarity_released_at = $submission->isSimilarityReleased() ? null : now();
        $submission->save();

        return redirect()->back()->with(
            'status',
            $submission->isSimilarityReleased() ? 'Similarity status released to student.' : 'Similarity status hidden from student.'
        );
    }
}
