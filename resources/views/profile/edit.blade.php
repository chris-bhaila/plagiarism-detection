@php
    $roleStyles = [
        \App\Models\User::ROLE_STUDENT => ['bg' => 'bg-info-bg', 'fg' => 'text-info-ink', 'border' => 'border-info-border'],
        \App\Models\User::ROLE_TEACHER => ['bg' => 'bg-ok-bg', 'fg' => 'text-ok-deep', 'border' => 'border-ok-border'],
        \App\Models\User::ROLE_ADMIN => ['bg' => 'bg-warn-bg', 'fg' => 'text-warn-deep', 'border' => 'border-warn-border'],
    ][$user->role] ?? ['bg' => 'bg-slate-100', 'fg' => 'text-slate-900', 'border' => 'border-slate-300'];
@endphp

<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">Profile</h1>

        <div class="mt-7 flex flex-wrap gap-7 items-start">

            {{-- Account summary --}}
            <div class="flex-1 min-w-[260px] basis-[300px] sticky top-[84px]">
                <div class="bg-white border border-slate-300 rounded-sm p-6">
                    <div class="text-[17px] font-semibold">{{ $user->name }}</div>
                    <div class="mt-1 text-[13px] text-slate-800 break-all">{{ $user->email }}</div>

                    <span class="mt-4 inline-block text-[11.5px] font-semibold px-2.5 py-1 rounded-sm border {{ $roleStyles['bg'] }} {{ $roleStyles['fg'] }} {{ $roleStyles['border'] }} capitalize">
                        {{ $user->role }}
                    </span>

                    @if ($user->isStudent() || $stats)
                        <div class="mt-6 pt-6 border-t border-slate-200 grid gap-5" style="grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));">
                            @if ($user->isStudent())
                                <div>
                                    <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Faculty</div>
                                    <div class="mt-1.5 text-[15px] font-medium font-mono">{{ $user->faculty ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Semester</div>
                                    <div class="mt-1.5 text-[15px] font-medium tabular-nums">{{ $user->semester ?? '—' }}</div>
                                </div>
                            @endif

                            @foreach ($stats as $label => $value)
                                <div>
                                    <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ $label }}</div>
                                    <div class="mt-1.5 text-[15px] font-medium tabular-nums">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>

                        @if ($user->isStudent())
                            <p class="mt-4 text-[12px] text-slate-700">Faculty and semester are set once and can't be changed here — contact your administrator if either is wrong.</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Forms --}}
            <div class="flex-1 min-w-[320px] basis-[560px] grid gap-7">
                <div class="bg-white border border-slate-300 rounded-sm p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="bg-white border border-slate-300 rounded-sm p-6">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="bg-white border border-slate-300 rounded-sm p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
