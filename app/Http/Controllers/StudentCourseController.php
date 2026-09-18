<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsStudentAssignmentRows;
use App\Models\Course;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCourseController extends Controller
{
    use BuildsStudentAssignmentRows;

    public function __construct(
        protected CourseRepositoryInterface $courses,
        protected AssignmentRepositoryInterface $assignments,
        protected SubmissionRepositoryInterface $submissions,
    ) {}

    /**
     * Read-only list of the courses the student is auto-enrolled in via
     * their semester — mirrors the teacher/admin course lists, but with
     * no roster or management actions, since a student has nothing to do
     * here besides see what they're enrolled in and who teaches it.
     */
    public function index(Request $request): View
    {
        return view('student.courses.index', [
            'courses' => $this->courses->forStudent($request->user()),
        ]);
    }

    /**
     * Everything about one course the student can see: its assignments,
     * each with the student's own submission status/feedback — same shape
     * as the cross-course assignments list, just scoped to one course.
     * Still no roster: see the "no roster" note on index() above, same
     * privacy reasoning applies here.
     */
    public function show(Request $request, Course $course): View
    {
        $student = $request->user();

        abort_unless($course->semester_id === $student->semester_id, 404);

        $course->loadMissing('teacher');

        $rows = $this->buildStudentAssignmentRows(
            $this->submissions,
            $student,
            $this->assignments->forCourse($course),
        );

        return view('student.courses.show', ['course' => $course, 'rows' => $rows]);
    }
}
