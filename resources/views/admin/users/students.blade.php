<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">Students</h1>
        <div class="mt-1 text-[13px] text-slate-900">{{ $students->count() }} {{ Str::plural('student', $students->count()) }}</div>

        <form method="GET" action="{{ route('admin.students') }}" class="mt-7 flex flex-wrap gap-2.5">
            <select name="faculty" class="bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                <option value="">Any faculty</option>
                @foreach ($faculties as $faculty)
                    <option value="{{ $faculty }}" @selected($filters['faculty'] === $faculty)>{{ $faculty }}</option>
                @endforeach
            </select>

            <select name="semester" class="bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                <option value="">Any semester</option>
                @for ($s = 1; $s <= 8; $s++)
                    <option value="{{ $s }}" @selected($filters['semester'] === $s)>Semester {{ $s }}</option>
                @endfor
            </select>

            <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">
                Filter
            </button>

            @if ($filters['faculty'] || $filters['semester'])
                <a href="{{ route('admin.students') }}" class="text-[13px] font-medium text-slate-800 hover:text-ink self-center">
                    Clear
                </a>
            @endif
        </form>

        <div class="mt-5 bg-white border border-slate-300 rounded-sm">
            <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,1.3fr)_90px_100px_130px_90px] gap-4 px-6 py-3 border-b border-slate-300 text-[11px] font-semibold tracking-wide uppercase text-slate-800">
                <div>Name</div>
                <div>Email</div>
                <div>Faculty</div>
                <div>Semester</div>
                <div class="text-right">Enrolled courses</div>
                <div></div>
            </div>

            @forelse ($students as $student)
                <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,1.3fr)_90px_100px_130px_90px] gap-4 px-6 py-4 border-b border-slate-200 last:border-b-0 items-center hover:bg-slate-50">
                    <div class="text-[14.5px] font-medium flex items-center gap-2">
                        {{ $student->name }}
                        @if ($student->isDisabled())
                            <span class="text-[10px] font-bold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-ink border border-danger-border">Disabled</span>
                        @endif
                    </div>
                    <div class="text-[13px] text-slate-800">{{ $student->email }}</div>
                    <div class="text-[13px] font-mono">{{ $student->faculty ?? '—' }}</div>
                    <div class="text-[13px] tabular-nums">{{ $student->semester ?? '—' }}</div>
                    <div class="text-right text-[14px] font-medium tabular-nums">{{ $student->enrolled_courses_count }}</div>
                    <div class="text-right">
                        <a href="{{ route('admin.users.edit', $student) }}" class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                            Edit
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No students match this filter.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
