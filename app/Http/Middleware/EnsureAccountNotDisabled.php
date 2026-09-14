<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catches a user disabled mid-session — LoginRequest blocks the login
 * attempt itself, but an already-active session needs its own check so
 * disabling someone takes effect immediately, not just on their next login.
 */
class EnsureAccountNotDisabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDisabled()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been disabled. Contact an administrator.']);
        }

        return $next($request);
    }
}
