@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-slate-300 sticky top-0 z-20">
    <div class="max-w-[1360px] mx-auto px-8 flex items-center gap-10 h-[60px]">
        <!-- Logo -->
        <a href="{{ route(Auth::user()->homeRouteName()) }}" class="flex items-baseline gap-2.5 shrink-0">
            <span class="relative top-[1px] inline-block w-[18px] h-[18px] rounded-[3px] border-[2.5px] border-navy">
                <span class="absolute left-[1px] top-[4px] w-[9px] h-[2px] bg-navy"></span>
            </span>
            <span class="text-base font-semibold tracking-tight text-ink">IntegrityCheck</span>
        </a>

        <!-- Navigation Links -->
        <div class="hidden sm:flex gap-1 self-stretch">
            @if (Auth::user()->isStudent())
                <x-nav-tab :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                    Dashboard
                </x-nav-tab>
                <x-nav-tab :href="route('student.courses.index')" :active="request()->routeIs('student.courses.*')">
                    My Courses
                </x-nav-tab>
                <x-nav-tab :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">
                    My Assignments
                </x-nav-tab>
            @endif

            @if (Auth::user()->isTeacher())
                <x-nav-tab :href="route('courses.index')" :active="request()->routeIs('courses.*')">
                    Courses
                </x-nav-tab>
            @endif

            @if (Auth::user()->isTeacher() || Auth::user()->isAdmin())
                <x-nav-tab :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Analytics
                </x-nav-tab>
            @endif

            @if (Auth::user()->isAdmin())
                <x-nav-tab :href="route('admin.teachers')" :active="request()->routeIs('admin.teachers')">
                    Teachers
                </x-nav-tab>
                <x-nav-tab :href="route('admin.students')" :active="request()->routeIs('admin.students')">
                    Students
                </x-nav-tab>
                <x-nav-tab :href="route('admin.faculties.index')" :active="request()->routeIs('admin.faculties.*')">
                    Faculties
                </x-nav-tab>
            @endif
        </div>

        <!-- Right side -->
        <div class="ml-auto flex items-center gap-3.5">
            <span class="text-[12.5px] text-slate-800 capitalize hidden sm:inline">{{ Auth::user()->role }}</span>

            <div class="hidden sm:block relative" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
                <button @click="menuOpen = !menuOpen" class="w-7 h-7 rounded-full bg-info-bg text-navy text-[11.5px] font-semibold grid place-items-center">
                    {{ $initials }}
                </button>

                <div x-show="menuOpen" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white border border-slate-300 rounded-sm shadow-lg py-1 text-sm z-30">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-ink hover:bg-slate-50">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-ink hover:bg-slate-50">Log Out</button>
                    </form>
                </div>
            </div>

            <!-- Hamburger -->
            <button @click="open = ! open" class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-800">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-cloak x-transition class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::user()->isStudent())
                <x-responsive-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                    Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.courses.index')" :active="request()->routeIs('student.courses.*')">
                    My Courses
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">
                    My Assignments
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->isTeacher())
                <x-responsive-nav-link :href="route('courses.index')" :active="request()->routeIs('courses.*')">
                    Courses
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->isTeacher() || Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Analytics
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.teachers')" :active="request()->routeIs('admin.teachers')">
                    Teachers
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.students')" :active="request()->routeIs('admin.students')">
                    Students
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.faculties.index')" :active="request()->routeIs('admin.faculties.*')">
                    Faculties
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-slate-300">
            <div class="px-4">
                <div class="font-medium text-base text-ink">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-800">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profile
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
