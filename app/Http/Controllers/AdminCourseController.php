<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courses,
        protected AssignmentRepositoryInterface $assignments,
    ) {}

    /**
     * Full detail for one course: its teacher/semester, auto-enrolled
     * roster, and its assignments (each linking through to submissions /
     * similarity reports). Read-only — admins don't create assignments or
     * change review status here.
     */
    public function show(Course $course): View
    {
        $course->load(['teacher', 'semester.faculty']);

        $roster = $course->students()->orderBy('name')->get();
        $assignments = $this->assignments->forCourse($course);

        return view('admin.courses.show', compact('course', 'roster', 'assignments'));
    }

    /**
     * Every course, with teacher and semester/faculty shown.
     */
    public function index(): View
    {
        $courses = $this->courses->all()->load(['teacher', 'semester.faculty']);

        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Course creation form: pick a faculty+semester and, optionally, a
     * teacher (courses can be created unassigned and given a teacher
     * later via edit()).
     */
    public function create(): View
    {
        return view('admin.courses.create', [
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('courses')],
            'semester_id' => ['required', Rule::exists('semesters', 'id')],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER)],
        ]);

        $course = $this->courses->create($validated);

        return redirect()->route('admin.semesters.show', $course->semester_id)
            ->with('status', "Course \"{$course->name}\" created.");
    }

    /**
     * Edit a course — primarily for assigning/reassigning its teacher, but
     * also allows fixing the name/code/semester.
     */
    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course,
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('courses')->ignore($course->id)],
            'semester_id' => ['required', Rule::exists('semesters', 'id')],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER)],
        ]);

        $this->courses->update($course, $validated);

        return redirect()->route('admin.semesters.show', $validated['semester_id'])
            ->with('status', "Course \"{$course->name}\" updated.");
    }

    /**
     * Delete a course. Cascades to its assignments, submissions, and
     * similarity reports (see the FK constraints) — irreversible, so the
     * UI confirms before submitting this. Attachment files aren't covered
     * by the DB cascade, so they're removed from disk explicitly first.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $semesterId = $course->semester_id;
        $name = $course->name;

        $course->assignments()
            ->whereNotNull('attachment_path')
            ->pluck('attachment_path')
            ->each(fn (string $path) => Storage::delete($path));

        $this->courses->delete($course);

        return redirect()->route('admin.semesters.show', $semesterId)
            ->with('status', "Course \"{$name}\" deleted.");
    }
}
