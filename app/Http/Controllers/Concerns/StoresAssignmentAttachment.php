<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Shared by AssignmentController (teacher) and AdminAssignmentController —
 * pure storage plumbing, not business/permission logic, so unlike the rest
 * of the teacher/admin controller pairs (kept intentionally duplicated,
 * see CLAUDE.md) this bit is fine to share.
 */
trait StoresAssignmentAttachment
{
    /**
     * @return array{attachment_path?: string, attachment_name?: string}
     */
    protected function storeAssignmentAttachment(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('assignment-attachments'),
            'attachment_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * For editing an existing assignment: uploading a new file replaces
     * the old one (old file deleted); checking "remove" with no new file
     * clears it; otherwise the existing attachment is left untouched (an
     * empty array — nothing to merge in).
     *
     * @return array{attachment_path?: ?string, attachment_name?: ?string}
     */
    protected function replaceAssignmentAttachment(Request $request, Assignment $assignment): array
    {
        $new = $this->storeAssignmentAttachment($request);

        if ($new !== []) {
            if ($assignment->hasAttachment()) {
                Storage::delete($assignment->attachment_path);
            }

            return $new;
        }

        if ($request->boolean('remove_attachment') && $assignment->hasAttachment()) {
            Storage::delete($assignment->attachment_path);

            return ['attachment_path' => null, 'attachment_name' => null];
        }

        return [];
    }
}
