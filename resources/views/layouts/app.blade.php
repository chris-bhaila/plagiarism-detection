<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Integrity Check') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        // The single source of truth for page container width — see
        // App\View\Components\AppLayout. Individual page views must not
        // redeclare their own max-w/mx-auto/px-8 wrapper.
        $containerClass = match ($maxWidth) {
            'narrow' => 'max-w-[640px]',
            'form' => 'max-w-[760px]',
            default => 'max-w-[1360px]',
        };
    @endphp
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-slate-300">
                    <div class="{{ $containerClass }} mx-auto py-6 px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                <div class="{{ $containerClass }} mx-auto px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
