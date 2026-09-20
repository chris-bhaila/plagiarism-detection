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

class AdminAssignmentController extends Controller
{
    use BuildsSubmissionRows, StoresAssignmentAttachment;

    public function __construct(
        protected SubmissionRepositoryInterface $submissions,
        protected AssignmentRepositoryInterface $assignments,
    ) {}

    /**
     * Admins have the same assignment-management capability as a
     * course's teacher.
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

        return redirect()->route('admin.courses.show', $assignment->course_id)
            ->with('status', 'Assignment created.');
    }

    /**
     * Edit an assignment's details.
     */
    public function edit(Assignment $assignment): View
    {
        return view('admin.assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
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

        return redirect()->route('admin.courses.show', $assignment->course_id)
            ->with('status', 'Assignment updated.');
    }

    /**
     * Delete an assignment. Cascades to its submissions and their
     * similarity reports — irreversible, confirmed in the UI first.
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        $courseId = $assignment->course_id;
        $title = $assignment->title;

        if ($assignment->hasAttachment()) {
            Storage::delete($assignment->attachment_path);
        }

        $this->assignments->delete($assignment);

        return redirect()->route('admin.courses.show', $courseId)
            ->with('status', "Assignment \"{$title}\" deleted.");
    }

    /**
     * Mirrors AssignmentController::submissions() for admins.
     */
    public function submissions(Request $request, Assignment $assignment): View
    {
        $threshold = $assignment->similarity_threshold;
        $rows = $this->buildSubmissionRows($this->submissions, $assignment);

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

        $rows = $this->searchAndSortRows($filtered, $request);

        $filters = [
            ['key' => 'all', 'label' => 'All '.$totalCount],
            ['key' => 'flagged', 'label' => 'Flagged '.$flaggedCount],
            ['key' => 'pending', 'label' => 'Pending review '.$pendingCount],
            ['key' => 'cleared', 'label' => 'Cleared '.$clearedCount],
        ];

        return view('admin.assignments.submissions', [
            'assignment' => $assignment,
            'rows' => $rows,
            'flaggedCount' => $flaggedCount,
            'pendingCount' => $pendingCount,
            'medianScore' => $medianScore,
            'activeFilter' => $filter,
            'totalCount' => $totalCount,
            'search' => trim((string) $request->query('search', '')),
            'sort' => in_array($request->query('sort'), ['name', 'submitted', 'words'], true) ? $request->query('sort') : 'score',
            'filters' => $filters,
        ]);
    }

    /**
     * Mirrors AssignmentController::exportSubmissions() for admins — no
     * ownership check, admins can export any course's scores.
     */
    public function exportSubmissions(Assignment $assignment): StreamedResponse
    {
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
