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
        <div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 px-6 py-12">
            <div class="w-full max-w-[380px]">
                <div class="flex items-baseline gap-2.5 justify-center mb-8">
                    <span class="relative top-[1px] inline-block w-[18px] h-[18px] rounded-[3px] border-[2.5px] border-navy">
                        <span class="absolute left-[1px] top-[4px] w-[9px] h-[2px] bg-navy"></span>
                    </span>
                    <span class="text-base font-semibold tracking-tight text-ink">IntegrityCheck</span>
                </div>

                <div class="bg-white border border-slate-300 rounded-sm px-8 py-9">
                    <h1 class="text-[20px] font-semibold tracking-tight">One more step</h1>
                    <p class="mt-1.5 text-[13.5px] text-slate-900">Tell us your faculty and semester so your teachers can find you when enrolling students.</p>

                    <form method="POST" action="{{ route('profile.complete.store') }}" class="mt-7 grid gap-5">
                        @csrf

                        <div>
                            <x-input-label for="faculty" value="Faculty" />
                            <select id="faculty" name="faculty" required
                                class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                                <option value="" disabled @selected(old('faculty') === null)>Select faculty</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty }}" @selected(old('faculty') === $faculty)>{{ $faculty }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('faculty')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="semester" value="Semester" />
                            <select id="semester" name="semester" required
                                class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                                <option value="" disabled @selected(old('semester') === null)>Select semester</option>
                                @for ($s = 1; $s <= 8; $s++)
                                    <option value="{{ $s }}" @selected((int) old('semester') === $s)>Semester {{ $s }}</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('semester')" class="mt-1.5" />
                        </div>

                        <x-primary-button class="w-full py-3 text-[14.5px]">
                            Continue
                        </x-primary-button>
                    </form>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
                    @csrf
                    <button type="submit" class="text-[13px] font-medium text-slate-800 hover:text-ink">Log out instead</button>
                </form>
            </div>
        </div>
    </body>
</html>
