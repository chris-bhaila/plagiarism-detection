<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\SimilarityReport;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected AssignmentRepositoryInterface $assignments,
    ) {}

    /**
     * All teacher accounts, filterable by name/email search text.
     */
    public function teachers(Request $request): View
    {
        $filters = ['search' => $request->query('search')];

        return view('admin.users.teachers', [
            'teachers' => $this->users->teachers($filters['search']),
            'filters' => $filters,
        ]);
    }

    /**
     * All student accounts, filterable by faculty and/or semester.
     */
    public function students(Request $request): View
    {
        $filters = [
            'faculty_id' => $request->filled('faculty_id') ? (int) $request->query('faculty_id') : null,
            'semester_id' => $request->filled('semester_id') ? (int) $request->query('semester_id') : null,
        ];

        return view('admin.users.students', [
            'students' => $this->users->students($filters),
            'filters' => $filters,
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
        ]);
    }

    /**
     * New-account form — student or teacher only. Admin accounts are
     * provisioned directly against the database, not through this UI; an
     * existing account's role (including up to admin) can still be
     * changed via edit()/update().
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_TEACHER])],
            'semester_id' => ['nullable', 'required_if:role,'.User::ROLE_STUDENT, Rule::exists('semesters', 'id')],
        ]);

        $user = $this->users->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'semester_id' => $validated['role'] === User::ROLE_STUDENT ? $validated['semester_id'] : null,
        ]);

        return redirect()->route($user->isStudent() ? 'admin.students' : 'admin.teachers')
            ->with('status', "{$user->name} created.");
    }

    /**
     * A student's profile: faculty/semester, their auto-enrolled courses,
     * and every assignment across those courses with this student's
     * submission status (if any) on each. A teacher's profile: the
     * courses they teach, each with roster/assignment/pending-review
     * counts.
     */
    public function show(User $user): View
    {
        abort_unless($user->isStudent() || $user->isTeacher(), 404);

        if ($user->isTeacher()) {
            $courses = $user->coursesTaught()->with('semester.faculty')->get()
                ->map(function ($course) {
                    $assignmentIds = $course->assignments()->pluck('id');

                    $pendingCount = SimilarityReport::where('status', SimilarityReport::STATUS_PENDING)
                        ->where(fn ($query) => $query
                            ->whereHas('submissionA', fn ($q) => $q->whereIn('assignment_id', $assignmentIds))
                            ->orWhereHas('submissionB', fn ($q) => $q->whereIn('assignment_id', $assignmentIds)))
                        ->count();

                    return (object) [
                        'course' => $course,
                        'studentCount' => $course->students()->count(),
                        'assignmentCount' => $assignmentIds->count(),
                        'pendingCount' => $pendingCount,
                    ];
                });

            return view('admin.users.show-teacher', compact('user', 'courses'));
        }

        $user->load('semester.faculty');

        $courses = $user->enrolledCourses()->with('teacher')->get();

        $assignments = $this->assignments->forStudent($user)
            ->map(function ($assignment) use ($user) {
                $submission = Submission::where('assignment_id', $assignment->id)
                    ->where('student_id', $user->id)
                    ->first();

                $topReport = $submission?->similarityReports()->first();

                return (object) [
                    'assignment' => $assignment,
                    'submission' => $submission,
                    'topReport' => $topReport,
                ];
            })
            ->sortByDesc(fn ($row) => $row->assignment->due_date)
            ->values();

        return view('admin.users.show', compact('user', 'courses', 'assignments'));
    }

    /**
     * Edit a single user's account — the one place faculty/semester can
     * still be changed once set, since a student can no longer do it
     * themselves.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'editedUser' => $user,
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_TEACHER, User::ROLE_ADMIN])],
            'semester_id' => ['nullable', 'required_if:role,'.User::ROLE_STUDENT, Rule::exists('semesters', 'id')],
        ]);

        // Admins can't demote themselves — a lone admin doing this would
        // lock themselves out of the admin-only pages immediately.
        abort_if(
            $user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN,
            403,
            "You can't change your own role away from admin.",
        );

        // Semester is a student-only field; clear it for anyone else so a
        // former student doesn't carry a stale value around.
        if ($validated['role'] !== User::ROLE_STUDENT) {
            $validated['semester_id'] = null;
        }

        $this->users->update($user, $validated);

        return redirect()->route('admin.users.edit', $user)
            ->with('status', 'User updated.');
    }

    /**
     * Flip a user's disabled status. A disabled user is logged out
     * immediately (EnsureAccountNotDisabled) and can't log back in
     * (LoginRequest) until re-enabled.
     */
    public function toggleDisabled(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, "You can't disable your own account.");

        $this->users->setDisabled($user, ! $user->isDisabled());

        return redirect()->route('admin.users.edit', $user)
            ->with('status', $user->fresh()->isDisabled() ? 'Account disabled.' : 'Account re-enabled.');
    }
}
