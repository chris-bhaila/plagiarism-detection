<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\SubmissionNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSubmissionNoteController extends Controller
{
    /**
     * Mirrors SubmissionNoteController::store() for admins — no ownership
     * check, admin can note any course's submissions.
     */
    public function store(Request $request, Submission $submission): RedirectResponse
    {
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
     * Mirrors SubmissionNoteController::update()/destroy() for admins — no
     * ownership or authorship check, admin can edit or delete any note.
     */
    public function update(Request $request, SubmissionNote $submissionNote): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $submissionNote->update(['body' => $validated['body']]);

        return redirect()->back()->with('status', 'Note updated.');
    }

    public function destroy(SubmissionNote $submissionNote): RedirectResponse
    {
        $submissionNote->delete();

        return redirect()->back()->with('status', 'Note deleted.');
    }
}
