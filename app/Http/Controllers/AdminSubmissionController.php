<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubmissionController extends Controller
{
    /**
     * Mirrors SubmissionController::show() for admins — no ownership check.
     */
    public function show(Request $request, Submission $submission): View
    {
        $submission->load(['student', 'assignment.course', 'notes.author']);

        return view('admin.submissions.show', [
            'submission' => $submission,
            'topReport' => $submission->topSimilarityReport(),
            'wordCount' => str_word_count(strip_tags($submission->text_content)),
        ]);
    }
}
