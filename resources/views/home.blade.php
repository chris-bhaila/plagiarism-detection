<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Integrity Check') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-ink">
        <div class="min-h-screen flex flex-col items-center justify-center px-4">
            <div class="max-w-xl text-center">
                <div class="flex items-center justify-center gap-2.5 mb-6">
                    <span class="relative top-[1px] inline-block w-[22px] h-[22px] rounded-[4px] border-[3px] border-navy">
                        <span class="absolute left-[2px] top-[6px] w-[11px] h-[2.5px] bg-navy"></span>
                    </span>
                    <span class="text-xl font-semibold tracking-tight">IntegrityCheck</span>
                </div>

                <p class="text-slate-900 mb-8 text-[14.5px] leading-relaxed">
                    Academic plagiarism &amp; similarity detection for student assignment submissions.
                </p>

                <div class="flex justify-center gap-3">
                    @auth
                        <a href="{{ route(Auth::user()->homeRouteName()) }}" class="px-4 py-2.5 bg-navy hover:bg-navy-light text-white rounded-sm text-sm font-semibold">
                            Go to my dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2.5 bg-navy hover:bg-navy-light text-white rounded-sm text-sm font-semibold">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2.5 bg-white border border-slate-500 rounded-sm text-sm font-semibold text-navy hover:bg-info-bg hover:border-navy-light">
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </body>
</html>
