<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSubmissionSimilarityReleaseController extends Controller
{
    /**
     * Mirrors SubmissionSimilarityReleaseController::update() for admins —
     * no ownership check, admin can release any course's submissions.
     */
    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $submission->similarity_released_at = $submission->isSimilarityReleased() ? null : now();
        $submission->save();

        return redirect()->back()->with(
            'status',
            $submission->isSimilarityReleased() ? 'Similarity status released to student.' : 'Similarity status hidden from student.'
        );
    }

    /**
     * Mirrors SubmissionSimilarityReleaseController::bulk() for admins —
     * no ownership check.
     */
    public function bulk(Request $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validate(['action' => ['required', 'in:release,hide']]);

        if ($validated['action'] === 'release') {
            $count = $assignment->submissions()->whereNull('similarity_released_at')->update(['similarity_released_at' => now()]);

            return redirect()->back()->with('status', "Released similarity status to {$count} ".str('student')->plural($count).'.');
        }

        $count = $assignment->submissions()->whereNotNull('similarity_released_at')->update(['similarity_released_at' => null]);

        return redirect()->back()->with('status', "Hid similarity status from {$count} ".str('student')->plural($count).'.');
    }
}
