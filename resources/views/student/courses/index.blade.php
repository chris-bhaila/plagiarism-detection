<x-app-layout>
    <div class="pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">My Courses</h1>
        <p class="mt-1.5 text-[13px] text-slate-900">You're automatically enrolled in every course under your semester.</p>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            @forelse ($courses as $course)
                <a href="{{ route('student.courses.show', $course) }}"
                   class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $course->name }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800 flex items-center gap-2 flex-wrap">
                            <span class="font-mono">{{ $course->code }}</span>
                            <span>&middot;</span>
                            @if ($course->teacher)
                                <span>{{ $course->teacher->name }}</span>
                            @else
                                <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border">Unassigned</span>
                            @endif
                        </div>
                    </div>
                    <span class="text-[12.5px] text-slate-800 text-right whitespace-nowrap">
                        {{ $course->assignments_count }} {{ Str::plural('assignment', $course->assignments_count) }}
                    </span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No courses in your semester yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
