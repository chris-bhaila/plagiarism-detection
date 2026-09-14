<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the browser from serving pages out of its back/forward cache after
 * logout. Without this, pressing "back" after logging out can redisplay a
 * page like /dashboard straight from cache — no request ever reaches the
 * server to notice the session is gone. `Cache-Control: no-store` is what
 * actually excludes a page from bfcache, forcing a real request on back
 * navigation, which then correctly redirects to login.
 */
class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
