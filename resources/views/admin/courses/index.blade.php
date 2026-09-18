<x-app-layout>
    <div class="pt-10 pb-20">

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight">Courses</h1>
                <div class="mt-1 text-[13px] text-slate-900">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</div>
            </div>
            <a href="{{ route('admin.courses.create') }}"
               class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                + New course
            </a>
        </div>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,0.9fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_70px] gap-4 px-6 py-3 border-b border-slate-300 text-[11px] font-semibold tracking-wide uppercase text-slate-800">
                <div>Course</div>
                <div>Code</div>
                <div>Faculty / Semester</div>
                <div>Teacher</div>
                <div></div>
            </div>

            @forelse ($courses as $course)
                <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,0.9fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_70px] gap-4 px-6 py-4 border-b border-slate-200 last:border-b-0 items-center hover:bg-slate-50">
                    <a href="{{ route('admin.courses.show', $course) }}" class="text-[14.5px] font-medium text-ink hover:text-navy hover:underline">{{ $course->name }}</a>
                    <div class="text-[13px] font-mono text-slate-800">{{ $course->code }}</div>
                    <div class="text-[13px] text-slate-800">{{ $course->semester->label() }}</div>
                    <div class="text-[13px]">
                        @if ($course->teacher)
                            <span class="text-slate-800">{{ $course->teacher->name }}</span>
                        @else
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border">Unassigned</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <a href="{{ route('admin.courses.edit', $course) }}" class="text-[12px] font-semibold text-navy hover:underline">Edit</a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No courses yet — create the first one.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
