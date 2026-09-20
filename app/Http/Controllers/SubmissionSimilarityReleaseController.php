<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
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

    /**
     * Release (or hide) similarity status for every submission to an
     * assignment at once — only the course's own teacher may.
     */
    public function bulk(Request $request, Assignment $assignment): RedirectResponse
    {
        abort_if($assignment->course->teacher_id !== $request->user()->id, 403);

        $validated = $request->validate(['action' => ['required', 'in:release,hide']]);

        return $this->applyBulk($assignment, $validated['action']);
    }

    protected function applyBulk(Assignment $assignment, string $action): RedirectResponse
    {
        if ($action === 'release') {
            $count = $assignment->submissions()->whereNull('similarity_released_at')->update(['similarity_released_at' => now()]);

            return redirect()->back()->with('status', "Released similarity status to {$count} ".str('student')->plural($count).'.');
        }

        $count = $assignment->submissions()->whereNotNull('similarity_released_at')->update(['similarity_released_at' => null]);

        return redirect()->back()->with('status', "Hid similarity status from {$count} ".str('student')->plural($count).'.');
    }
}
