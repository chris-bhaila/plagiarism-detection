<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsStudentAssignmentRows;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    use BuildsStudentAssignmentRows;

    public function __construct(
        protected CourseRepositoryInterface $courses,
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
    ) {}

    /**
     * A student's home page: unlike the teacher/admin analytics dashboard
     * (DashboardController — flag rates, scores, nothing a student should
     * see), this is a personal snapshot — what's due, what's new — built
     * from the same assignment+submission rows as the assignments list and
     * course detail page (see BuildsStudentAssignmentRows).
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        $courses = $this->courses->forStudent($student);
        $rows = $this->buildStudentAssignmentRows($this->submissions, $student, $this->assignments->forStudent($student));

        $submittedRows = $rows->filter(fn ($row) => $row->submission)->values();
        $notSubmittedRows = $rows->filter(fn ($row) => ! $row->submission)->values();
        $overdueCount = $notSubmittedRows->filter(
            fn ($row) => $row->assignment->due_date && $row->assignment->due_date->isPast()
        )->count();

        // Rows are already sorted by due date (undated pushed last) by
        // buildStudentAssignmentRows, so this naturally surfaces overdue
        // assignments first, then the soonest-due ones.
        $dueSoonRows = $notSubmittedRows->take(5);

        $unseenRows = $submittedRows->filter(fn ($row) => $row->submission->hasUnseenActivity())->values();

        return view('student.dashboard', [
            'coursesCount' => $courses->count(),
            'totalAssignments' => $rows->count(),
            'submittedCount' => $submittedRows->count(),
            'overdueCount' => $overdueCount,
            'unseenCount' => $unseenRows->count(),
            'dueSoonRows' => $dueSoonRows,
            'unseenRows' => $unseenRows->take(5),
        ]);
    }
}
