<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionNoteController extends Controller
{
    /**
     * Add a follow-up note to a submission — only the course's own
     * teacher may. Teacher/admin-facing only; no student-visible surface
     * yet (deliberately deferred).
     */
    public function store(Request $request, Submission $submission): RedirectResponse
    {
        abort_if($submission->assignment->course->teacher_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $submission->notes()->create([
            'author_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()->back()->with('status', 'Note added.');
    }
}
