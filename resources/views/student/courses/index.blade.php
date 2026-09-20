<x-app-layout>
    <div class="pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">My Courses</h1>
        @if ($semester)
            <p class="mt-1 text-[13px] text-slate-900">{{ $semester->label() }} &middot; you're enrolled in every course of your semester automatically.</p>
        @endif

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            @forelse ($rows as $row)
                @php $course = $row->course; @endphp
                <div class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $course->name }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800">
                            <span class="font-mono">{{ $course->code }}</span>
                            &middot;
                            @if ($course->teacher)
                                {{ $course->teacher->name }}
                            @else
                                <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-900 border border-slate-300">Unassigned</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-5 text-right">
                        <div class="text-[12.5px] text-slate-800">
                            <div>{{ $row->submittedCount }} / {{ $row->assignmentCount }} {{ Str::plural('assignment', $row->assignmentCount) }} submitted</div>
                            @if ($row->nextDue)
                                <div class="mt-0.5">Next due {{ $row->nextDue->due_date->format('j M Y, H:i') }}</div>
                            @endif
                        </div>
                        <a href="{{ route('assignments.index', ['search' => $course->code]) }}"
                           class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                            Assignments
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No courses in your semester yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
