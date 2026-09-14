<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * All teacher accounts. Faculty/semester don't apply to teachers, so
     * there's nothing to filter by here — just the full list.
     */
    public function teachers(): View
    {
        return view('admin.users.teachers', [
            'teachers' => $this->users->teachers(),
        ]);
    }

    /**
     * All student accounts, filterable by faculty and/or semester.
     */
    public function students(Request $request): View
    {
        $filters = [
            'faculty' => $request->query('faculty'),
            'semester' => $request->filled('semester') ? (int) $request->query('semester') : null,
        ];

        return view('admin.users.students', [
            'students' => $this->users->students($filters),
            'filters' => $filters,
            'faculties' => User::FACULTIES,
        ]);
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
            'faculties' => User::FACULTIES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_TEACHER, User::ROLE_ADMIN])],
            'faculty' => ['nullable', 'required_if:role,'.User::ROLE_STUDENT, 'string', Rule::in(User::FACULTIES)],
            'semester' => ['nullable', 'required_if:role,'.User::ROLE_STUDENT, 'integer', 'between:1,8'],
        ]);

        // Admins can't demote themselves — a lone admin doing this would
        // lock themselves out of the admin-only pages immediately.
        abort_if(
            $user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN,
            403,
            "You can't change your own role away from admin.",
        );

        // Faculty/semester are student-only fields; clear them for anyone
        // else so a former student doesn't carry stale values around.
        if ($validated['role'] !== User::ROLE_STUDENT) {
            $validated['faculty'] = null;
            $validated['semester'] = null;
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
