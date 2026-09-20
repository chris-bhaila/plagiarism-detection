<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\SimilarityReport;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(protected AssignmentRepositoryInterface $assignments) {}

    /**
     * Student home: at-a-glance counts, what needs attention (overdue or
     * due soonest and not yet submitted), and recent submissions. Check
     * status on a recent submission only appears once released, matching
     * the receipt page.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        $assignments = $this->assignments->forStudent($student);
        $submissions = $student->submissions()
            ->with(['assignment.course', 'notes'])
            ->orderByDesc('submitted_at')
            ->get();

        $submittedIds = $submissions->pluck('assignment_id')->unique();
        $outstanding = $assignments->reject(fn (Assignment $a) => $submittedIds->contains($a->id));

        $overdue = $outstanding
            ->filter(fn (Assignment $a) => $a->due_date && $a->due_date->isPast())
            ->sortBy('due_date')
            ->values();

        $upcoming = $outstanding
            ->filter(fn (Assignment $a) => ! $a->due_date || $a->due_date->isFuture())
            ->sortBy(fn (Assignment $a) => $a->due_date ?? now()->addCentury())
            ->take(5)
            ->values();

        $recent = $submissions->unique('assignment_id')->take(5)->map(fn ($submission) => (object) [
            'submission' => $submission,
            'label' => $submission->isSimilarityReleased()
                ? SimilarityReport::studentFacingLabel($submission->topSimilarityReport()?->status)
                : null,
        ])->values();

        return view('student.dashboard', [
            'student' => $student,
            'courseCount' => $student->enrolledCourses()->count(),
            'assignmentCount' => $assignments->count(),
            'submittedCount' => $assignments->filter(fn (Assignment $a) => $submittedIds->contains($a->id))->count(),
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'noteCount' => $submissions->sum(fn ($submission) => $submission->notes->count()),
        ]);
    }
}
