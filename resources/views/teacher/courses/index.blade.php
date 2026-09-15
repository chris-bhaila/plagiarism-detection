<x-app-layout>
    <div class="pt-10 pb-20">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight">My Courses</h1>
                <p class="mt-1 text-[13px] text-slate-900">Courses are created and assigned by an administrator.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('courses.index') }}" class="mt-7 flex flex-wrap gap-2.5">
            <input type="text" name="search" value="{{ $search }}" placeholder="Name or code"
                class="flex-1 min-w-[200px] max-w-[320px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">

            <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">
                Search
            </button>

            @if ($search)
                <a href="{{ route('courses.index') }}" class="text-[13px] font-medium text-slate-800 hover:text-ink self-center">
                    Clear
                </a>
            @endif
        </form>

        <div class="mt-5 bg-white border border-slate-300 rounded-sm">
            @forelse ($courses as $course)
                <a href="{{ route('courses.show', $course) }}"
                   class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $course->name }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800 font-mono">{{ $course->code }}</div>
                    </div>
                    <span class="text-[12.5px] text-slate-800 text-right">
                        {{ $course->students()->count() }} {{ Str::plural('student', $course->students()->count()) }}
                        &middot;
                        {{ $course->assignments()->count() }} {{ Str::plural('assignment', $course->assignments()->count()) }}
                    </span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">
                    {{ $search ? 'No courses match this search.' : 'No courses assigned to you yet.' }}
                </p>
            @endforelse
        </div>
    </div>
</x-app-layout>
