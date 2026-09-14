<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courses,
    ) {}

    /**
     * List courses managed by the authenticated teacher.
     */
    public function index(Request $request): View
    {
        $courses = $this->courses->forTeacher($request->user());

        return view('teacher.courses.index', compact('courses'));
    }

    /**
     * Create a new course owned by the authenticated teacher.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('courses')->where('teacher_id', $request->user()->id),
            ],
        ]);

        $course = $this->courses->create([
            ...$validated,
            'teacher_id' => $request->user()->id,
        ]);

        return redirect()->route('courses.show', $course)
            ->with('status', 'Course created. Add students below.');
    }

    /**
     * Course detail: roster of enrolled students, plus a filterable search
     * to enroll more.
     */
    public function show(Request $request, Course $course): View
    {
        $this->authorizeTeacherOwnsCourse($request, $course);

        $filters = [
            'search' => $request->query('search'),
            'faculty' => $request->query('faculty'),
            'semester' => $request->filled('semester') ? (int) $request->query('semester') : null,
        ];

        $hasSearched = collect($filters)->filter()->isNotEmpty();

        $candidates = $hasSearched
            ? $this->courses->searchAvailableStudents($course, $filters)
            : collect();

        $roster = $course->students()->orderBy('name')->get();

        return view('teacher.courses.show', [
            'course' => $course,
            'roster' => $roster,
            'candidates' => $candidates,
            'hasSearched' => $hasSearched,
            'filters' => $filters,
            'faculties' => User::FACULTIES,
        ]);
    }

    /**
     * Enroll the selected students in the course.
     */
    public function enroll(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeTeacherOwnsCourse($request, $course);

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $this->courses->enrollStudents($course, $validated['student_ids']);

        $count = count($validated['student_ids']);

        return redirect()->route('courses.show', $course)
            ->with('status', $count === 1 ? '1 student enrolled.' : "{$count} students enrolled.");
    }

    protected function authorizeTeacherOwnsCourse(Request $request, Course $course): void
    {
        abort_if($course->teacher_id !== $request->user()->id, 403);
    }
}
