<?php

namespace App\Http\Controllers;

use App\Models\Submission;
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
}
