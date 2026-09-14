<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student who hasn't picked a faculty/semester (e.g. an account created
 * before these fields existed) gets redirected to fill them in before
 * doing anything else — needed so they show up correctly for teachers
 * searching students to enroll.
 */
class EnsureStudentProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $needsProfile = $user
            && $user->isStudent()
            && (! $user->faculty || ! $user->semester);

        $exempt = $request->routeIs('profile.complete', 'profile.complete.store', 'logout');

        if ($needsProfile && ! $exempt) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
