<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courses,
        protected AssignmentRepositoryInterface $assignments,
    ) {}

    /**
     * List courses managed by the authenticated teacher, filterable by
     * name/code search text.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $courses = $this->courses->forTeacher($request->user(), $search);

        return view('teacher.courses.index', [
            'courses' => $courses,
            'search' => $search,
        ]);
    }

    /**
     * Course detail: roster of students auto-enrolled via shared semester,
     * plus the course's assignments (create/edit/delete/review, all
     * inline here) — combined on one page, same as the admin equivalent.
     */
    public function show(Request $request, Course $course): View
    {
        $this->authorizeTeacherOwnsCourse($request, $course);

        $roster = $course->students()->orderBy('name')->get();
        $assignments = $this->assignments->forCourse($course);

        return view('teacher.courses.show', [
            'course' => $course,
            'roster' => $roster,
            'assignments' => $assignments,
        ]);
    }

    /**
     * One student's status within this course: every assignment, and
     * their submission (if any) with its similarity review status.
     */
    public function showStudent(Request $request, Course $course, User $student): View
    {
        $this->authorizeTeacherOwnsCourse($request, $course);

        abort_unless($student->isStudent() && $student->semester_id === $course->semester_id, 404);

        $assignments = $course->assignments()->orderByDesc('due_date')->get()
            ->map(function ($assignment) use ($student) {
                $submission = Submission::where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->first();

                return (object) [
                    'assignment' => $assignment,
                    'submission' => $submission,
                    'topReport' => $submission?->similarityReports()->first(),
                ];
            });

        return view('teacher.courses.student', [
            'course' => $course,
            'student' => $student,
            'assignments' => $assignments,
        ]);
    }

    protected function authorizeTeacherOwnsCourse(Request $request, Course $course): void
    {
        abort_if($course->teacher_id !== $request->user()->id, 403);
    }
}
