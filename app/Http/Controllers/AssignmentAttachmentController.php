<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentAttachmentController extends Controller
{
    /**
     * Serve an assignment's attachment from the private 'local' disk.
     * Reachable by any authenticated role, but scoped: a teacher must own
     * the course, a student must be in its semester, an admin always can.
     */
    public function download(Request $request, Assignment $assignment): StreamedResponse
    {
        $user = $request->user();
        $assignment->loadMissing('course');

        abort_unless(
            $user->isAdmin()
                || ($user->isTeacher() && $assignment->course->teacher_id === $user->id)
                || ($user->isStudent() && $assignment->course->semester_id === $user->semester_id),
            403,
        );

        abort_unless($assignment->hasAttachment(), 404);

        return Storage::download($assignment->attachment_path, $assignment->attachment_name);
    }
}
