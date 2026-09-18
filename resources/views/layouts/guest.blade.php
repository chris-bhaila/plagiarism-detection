<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Integrity Check') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-ink">
        @include('layouts.toasts')

        <div class="min-h-screen flex">

            <!-- Brand panel -->
            <div class="hidden lg:flex lg:w-[420px] shrink-0 bg-navy text-white flex-col justify-between px-12 py-14">
                <a href="{{ route('home') }}" class="flex items-baseline gap-2.5">
                    <span class="relative top-[1px] inline-block w-[20px] h-[20px] rounded-[4px] border-[2.5px] border-white">
                        <span class="absolute left-[2px] top-[5px] w-[10px] h-[2px] bg-white"></span>
                    </span>
                    <span class="text-lg font-semibold tracking-tight">IntegrityCheck</span>
                </a>

                <div>
                    <h1 class="text-[28px] font-semibold tracking-tight leading-tight">Academic integrity,<br>made visible.</h1>
                    <p class="mt-4 text-[14.5px] leading-relaxed text-slate-300 max-w-[36ch]">
                        Similarity detection for student assignment submissions — lexical and semantic checks, reviewed by the people who teach the course.
                    </p>

                    <ul class="mt-10 space-y-4 text-[13.5px] text-slate-300">
                        <li class="flex gap-3 items-start">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-navy-lighter shrink-0"></span>
                            Lexical + semantic similarity scoring
                        </li>
                        <li class="flex gap-3 items-start">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-navy-lighter shrink-0"></span>
                            Side-by-side overlap review
                        </li>
                        <li class="flex gap-3 items-start">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-navy-lighter shrink-0"></span>
                            Per-course flagging thresholds
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} IntegrityCheck</p>
            </div>

            <!-- Form panel -->
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 bg-slate-50">
                <div class="lg:hidden mb-8">
                    <a href="{{ route('home') }}" class="flex items-baseline gap-2.5">
                        <span class="relative top-[1px] inline-block w-[20px] h-[20px] rounded-[4px] border-[2.5px] border-navy">
                            <span class="absolute left-[2px] top-[5px] w-[10px] h-[2px] bg-navy"></span>
                        </span>
                        <span class="text-lg font-semibold tracking-tight text-ink">IntegrityCheck</span>
                    </a>
                </div>

                <div class="w-full max-w-[380px] bg-white border border-slate-300 rounded-sm px-8 py-9" style="animation: page-fade-in 220ms ease-out">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
