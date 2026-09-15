<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student who hasn't picked a semester (e.g. an account created before
 * this field existed) gets redirected to fill it in before doing anything
 * else — needed so they're auto-enrolled into the right courses.
 */
class EnsureStudentProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $needsProfile = $user
            && $user->isStudent()
            && ! $user->semester_id;

        $exempt = $request->routeIs('profile.complete', 'profile.complete.store', 'logout');

        if ($needsProfile && ! $exempt) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
