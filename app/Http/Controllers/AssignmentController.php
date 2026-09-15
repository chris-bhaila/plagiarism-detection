<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsSubmissionRows;
use App\Http\Controllers\Concerns\StoresAssignmentAttachment;
use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    use BuildsSubmissionRows, StoresAssignmentAttachment;

    public function __construct(
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
    ) {}

    /**
     * Create a new assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'attachment' => ['nullable', 'file', 'mimes:docx', 'max:10240'],
        ]);

        $assignment = $this->assignments->create([
            ...collect($validated)->except('attachment')->all(),
            'similarity_threshold' => $validated['similarity_threshold'] ?? Assignment::DEFAULT_SIMILARITY_THRESHOLD,
            ...$this->storeAssignmentAttachment($request),
        ]);

        return redirect()->route('courses.show', $assignment->course_id)
            ->with('status', 'Assignment created.');
    }

    /**
     * Edit an assignment's details — only the course's own teacher may.
     */
    public function edit(Request $request, Assignment $assignment): View
    {
        abort_if($assignment->course->teacher_id !== $request->user()->id, 403);

        return view('teacher.assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        abort_if($assignment->course->teacher_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'similarity_threshold' => ['nullable', 'numeric', 'between:0,1'],
            'attachment' => ['nullable', 'file', 'mimes:docx', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        $this->assignments->update($assignment, [
            ...collect($validated)->except(['attachment', 'remove_attachment'])->all(),
            // Left blank on edit: keep whatever threshold it already had,
            // rather than overwriting it with null.
            'similarity_threshold' => $validated['similarity_threshold'] ?? $assignment->similarity_threshold,
            ...$this->replaceAssignmentAttachment($request, $assignment),
        ]);

        return redirect()->route('courses.show', $assignment->course_id)
            ->with('status', 'Assignment updated.');
    }

    /**
     * Delete an assignment — only the course's own teacher may. Cascades
     * to its submissions and their similarity reports; confirmed in the
     * UI first since it's irreversible.
     */
    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        abort_if($assignment->course->teacher_id !== $request->user()->id, 403);

        $courseId = $assignment->course_id;
        $title = $assignment->title;

        if ($assignment->hasAttachment()) {
            Storage::delete($assignment->attachment_path);
        }

        $this->assignments->delete($assignment);

        return redirect()->route('courses.show', $courseId)
            ->with('status', "Assignment \"{$title}\" deleted.");
    }

    /**
     * Review submissions and similarity scores for an assignment.
     */
    public function submissions(Request $request, Assignment $assignment): View
    {
        $threshold = $assignment->similarity_threshold;
        $rows = $this->buildSubmissionRows($this->submissions, $assignment);

        // "Cleared" covers both a submission with no comparisons at all yet
        // (topReport is null) and one whose best comparison still scored
        // below the threshold (topReport->status is 'cleared').
        $isCleared = fn ($r) => $r->topReport === null || $r->status === SimilarityReport::STATUS_CLEARED;

        $totalCount = $rows->count();
        $flaggedCount = $rows->filter(fn ($r) => $r->score >= $threshold)->count();
        $pendingCount = $rows->filter(fn ($r) => $r->status === SimilarityReport::STATUS_PENDING)->count();
        $clearedCount = $rows->filter($isCleared)->count();
        $medianScore = $rows->pluck('score')->median() ?? 0.0;

        $filter = $request->query('filter', 'all');

        $filtered = match ($filter) {
            'flagged' => $rows->filter(fn ($r) => $r->score >= $threshold),
            'pending' => $rows->filter(fn ($r) => $r->status === SimilarityReport::STATUS_PENDING),
            'cleared' => $rows->filter($isCleared),
            default => $rows,
        };

        $rows = $filtered->sortByDesc('score')->values();

        $filters = [
            ['key' => 'all', 'label' => 'All '.$totalCount],
            ['key' => 'flagged', 'label' => 'Flagged '.$flaggedCount],
            ['key' => 'pending', 'label' => 'Pending review '.$pendingCount],
            ['key' => 'cleared', 'label' => 'Cleared '.$clearedCount],
        ];

        return view('teacher.assignments.submissions', [
            'assignment' => $assignment,
            'rows' => $rows,
            'flaggedCount' => $flaggedCount,
            'pendingCount' => $pendingCount,
            'medianScore' => $medianScore,
            'activeFilter' => $filter,
            'filters' => $filters,
        ]);
    }

    /**
     * CSV export of every submission's score and review status for an
     * assignment — always the full set, ignoring whatever filter is
     * active on the submissions page.
     */
    public function exportSubmissions(Request $request, Assignment $assignment): StreamedResponse
    {
        abort_if($assignment->course->teacher_id !== $request->user()->id, 403);

        $rows = $this->buildSubmissionRows($this->submissions, $assignment)->sortByDesc('score');

        $filename = Str::slug($assignment->title).'-scores.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'Student ID', 'Submitted At', 'Combined %', 'Lexical %', 'Semantic %', 'Status', 'Matched Submissions']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->submission->student->name,
                    'S-'.str_pad((string) $row->submission->student_id, 5, '0', STR_PAD_LEFT),
                    $row->submission->submitted_at?->format('Y-m-d H:i'),
                    round($row->score * 100),
                    round($row->lexical * 100),
                    round($row->semantic * 100),
                    $row->status ? ucfirst($row->status) : 'No match',
                    $row->matchCount,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
