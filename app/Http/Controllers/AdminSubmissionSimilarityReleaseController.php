<?php

namespace App\Http\Controllers;

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
}
