<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCourseController extends Controller
{
    /**
     * The student's auto-enrolled courses (every course in their semester),
     * each with how many of its assignments they've submitted and what's due next.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        $submittedAssignmentIds = $student->submissions()->pluck('assignment_id')->unique();

        $courses = $student->enrolledCourses()
            ->with(['teacher', 'assignments'])
            ->orderBy('courses.code')
            ->get();

        $rows = $courses->map(function (Course $course) use ($submittedAssignmentIds) {
            $assignmentIds = $course->assignments->pluck('id');
            $nextDue = $course->assignments
                ->filter(fn ($a) => $a->due_date && $a->due_date->isFuture() && ! $submittedAssignmentIds->contains($a->id))
                ->sortBy('due_date')
                ->first();

            return (object) [
                'course' => $course,
                'assignmentCount' => $assignmentIds->count(),
                'submittedCount' => $assignmentIds->intersect($submittedAssignmentIds)->count(),
                'nextDue' => $nextDue,
            ];
        });

        return view('student.courses.index', ['rows' => $rows, 'semester' => $student->semester]);
    }
}
