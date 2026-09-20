<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\SubmissionNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionNoteController extends Controller
{
    /**
     * Add a follow-up note to a submission — only the course's own
     * teacher may write one. The student it belongs to can read it
     * (read-only) on their own submission receipt page.
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

    /**
     * Edit a note — only its author, and only while they still teach the
     * course (mirrors store()'s ownership check).
     */
    public function update(Request $request, SubmissionNote $submissionNote): RedirectResponse
    {
        $this->authorizeOwnNote($request, $submissionNote);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $submissionNote->update(['body' => $validated['body']]);

        return redirect()->back()->with('status', 'Note updated.');
    }

    public function destroy(Request $request, SubmissionNote $submissionNote): RedirectResponse
    {
        $this->authorizeOwnNote($request, $submissionNote);

        $submissionNote->delete();

        return redirect()->back()->with('status', 'Note deleted.');
    }

    protected function authorizeOwnNote(Request $request, SubmissionNote $note): void
    {
        abort_if($note->submission->assignment->course->teacher_id !== $request->user()->id, 403);
        abort_if($note->author_id !== $request->user()->id, 403);
    }
}
