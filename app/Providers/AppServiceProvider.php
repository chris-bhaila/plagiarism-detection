<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The framework's default 'guest' redirect always tries the
        // 'dashboard' route first, which is teacher/admin-only here — a
        // logged-in student hitting a guest route (e.g. '/') would get
        // bounced there and 403. Send everyone to their own role's home.
        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request) => route($request->user()?->homeRouteName() ?? 'home'),
        );
    }
}
