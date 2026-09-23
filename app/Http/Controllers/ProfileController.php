<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form, along with a quick role-relevant
     * summary of their account.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $stats = match (true) {
            $user->isStudent() => [
                'Enrolled courses' => $user->enrolledCourses()->count(),
                'Submissions made' => $user->submissions()->count(),
            ],
            $user->isTeacher() => [
                'Courses taught' => $user->coursesTaught()->count(),
            ],
            default => [],
        };

        return view('profile.edit', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form prompting a student to fill in faculty/semester, shown
     * (via EnsureStudentProfileComplete) before they can use anything else.
     *
     * Faculty/semester are a one-time choice — a student who already has
     * both set is bounced away rather than allowed back in to change them.
     */
    public function completeForm(Request $request): View|RedirectResponse
    {
        if ($request->user()->semester_id) {
            return redirect()->route('assignments.index');
        }

        return view('profile.complete', [
            'faculties' => Faculty::with('semesters')->orderBy('name')->get(),
        ]);
    }

    /**
     * Save the student's semester and send them on their way. Refuses to
     * run again once already set — see completeForm().
     */
    public function completeStore(Request $request): RedirectResponse
    {
        abort_if($request->user()->semester_id, 403);

        $validated = $request->validate([
            'semester_id' => ['required', Rule::exists('semesters', 'id')],
        ]);

        $request->user()->update($validated);

        return redirect()->route('assignments.index')
            ->with('status', 'Profile completed.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Unlike a teacher or student, an admin has no other self-service
        // path back into the app once their account is gone — new admins
        // are provisioned directly against the database, not through the
        // UI (see AdminUserController::store's deliberate exclusion of the
        // admin role). Same reasoning as the existing self-demote and
        // self-disable guards in AdminUserController; this just extends it
        // to the one remaining way an admin could lock themselves out.
        if ($user->isAdmin()) {
            return back()->withErrors([
                'password' => "Admin accounts can't be deleted from here — ask another admin, or remove it directly in the database.",
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
