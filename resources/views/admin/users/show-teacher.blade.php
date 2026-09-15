<x-app-layout>
    <div class="pt-10 pb-20">
        <a href="{{ route('admin.teachers') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to teachers
        </a>

        <div class="mt-2.5 flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight flex items-center gap-2.5">
                    {{ $user->name }}
                    @if ($user->isDisabled())
                        <span class="text-[10.5px] font-bold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-ink border border-danger-border">Disabled</span>
                    @endif
                </h1>
                <div class="mt-1.5 text-[13px] text-slate-900">{{ $user->email }}</div>
            </div>
            <a href="{{ route('admin.users.edit', $user) }}"
               class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                Edit account
            </a>
        </div>

        <div class="mt-7 grid grid-cols-3 gap-5 max-w-[600px]">
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Courses</div>
                <div class="mt-1.5 text-[18px] font-semibold tabular-nums">{{ $courses->count() }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Students</div>
                <div class="mt-1.5 text-[18px] font-semibold tabular-nums">{{ $courses->sum('studentCount') }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Pending review</div>
                <div class="mt-1.5 text-[18px] font-semibold tabular-nums {{ $courses->sum('pendingCount') > 0 ? 'text-warn-deep' : '' }}">{{ $courses->sum('pendingCount') }}</div>
            </div>
        </div>

        <div class="mt-9">
            <h2 class="text-[15px] font-semibold tracking-tight">Courses taught</h2>
            <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_90px_100px_110px] gap-4 px-5 py-3 border-b border-slate-300 text-[11px] font-semibold tracking-wide uppercase text-slate-800">
                    <div>Course</div>
                    <div>Faculty / Semester</div>
                    <div class="text-right">Students</div>
                    <div class="text-right">Assignments</div>
                    <div class="text-right">Pending</div>
                </div>

                @forelse ($courses as $row)
                    <a href="{{ route('admin.courses.show', $row->course) }}"
                       class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_90px_100px_110px] gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 items-center hover:bg-slate-50">
                        <div class="min-w-0">
                            <div class="text-[14px] font-medium truncate">{{ $row->course->name }}</div>
                            <div class="mt-0.5 text-[12px] font-mono text-slate-700">{{ $row->course->code }}</div>
                        </div>
                        <div class="text-[13px] text-slate-800">{{ $row->course->semester->label() }}</div>
                        <div class="text-right text-[14px] tabular-nums">{{ $row->studentCount }}</div>
                        <div class="text-right text-[14px] tabular-nums">{{ $row->assignmentCount }}</div>
                        <div class="text-right text-[14px] tabular-nums {{ $row->pendingCount > 0 ? 'text-warn-deep font-semibold' : '' }}">{{ $row->pendingCount }}</div>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-800">No courses assigned yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
