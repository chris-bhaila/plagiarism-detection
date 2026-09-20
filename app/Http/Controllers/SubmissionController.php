<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    /**
     * A submission's full text, its best similarity match and follow-up notes — only for the course's own teacher.
     */
    public function show(Request $request, Submission $submission): View
    {
        abort_if($submission->assignment->course->teacher_id !== $request->user()->id, 403);

        $submission->load(['student', 'assignment.course', 'notes.author']);

        return view('teacher.submissions.show', [
            'submission' => $submission,
            'topReport' => $submission->topSimilarityReport(),
            'wordCount' => str_word_count(strip_tags($submission->text_content)),
        ]);
    }
}
