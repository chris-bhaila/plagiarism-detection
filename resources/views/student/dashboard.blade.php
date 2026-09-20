<x-app-layout>
    <div class="pt-10 pb-20">
        <h1 class="m-0 text-[28px] font-semibold tracking-tight">Hello, {{ Str::before($student->name, ' ') }}</h1>
        @if ($student->semester)
            <div class="mt-2.5 text-[13.5px] text-slate-900">{{ $student->semester->label() }}</div>
        @endif

        <div class="mt-7 grid gap-5" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <a href="{{ route('student.courses.index') }}" class="bg-white border border-slate-300 rounded-sm p-6 hover:bg-slate-50">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Courses</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ $courseCount }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">this semester</div>
            </a>
            <a href="{{ route('assignments.index') }}" class="bg-white border border-slate-300 rounded-sm p-6 hover:bg-slate-50">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Submitted</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ $submittedCount }}<span class="text-xl text-slate-800"> / {{ $assignmentCount }}</span></div>
                <div class="mt-3 text-[12.5px] text-slate-900">{{ Str::plural('assignment', $assignmentCount) }}</div>
            </a>
            <a href="{{ route('assignments.index', ['status' => 'overdue']) }}" class="bg-white border border-slate-300 rounded-sm p-6 hover:bg-slate-50">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Overdue</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums {{ $overdue->isNotEmpty() ? 'text-danger-ink' : '' }}">{{ $overdue->count() }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">not yet submitted</div>
            </a>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Instructor notes</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ $noteCount }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">on your submissions</div>
            </div>
        </div>

        <div class="mt-11 grid gap-11 items-start" style="grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));">
            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Needs your attention</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @foreach ($overdue as $assignment)
                        <a href="{{ route('assignments.submit.show', $assignment) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div>
                                <div class="text-[14px] font-medium">{{ $assignment->title }}</div>
                                <div class="mt-0.5 text-[12.5px] text-slate-800">{{ $assignment->course->code ?? '' }} &middot; was due {{ $assignment->due_date->format('j M Y, H:i') }}</div>
                            </div>
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-deep border border-danger-border">Overdue</span>
                        </a>
                    @endforeach
                    @foreach ($upcoming as $assignment)
                        <a href="{{ route('assignments.submit.show', $assignment) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div>
                                <div class="text-[14px] font-medium">{{ $assignment->title }}</div>
                                <div class="mt-0.5 text-[12.5px] text-slate-800">
                                    {{ $assignment->course->code ?? '' }}
                                    @if ($assignment->due_date) &middot; due {{ $assignment->due_date->format('j M Y, H:i') }} @endif
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-900 border border-slate-300">Not submitted</span>
                        </a>
                    @endforeach
                    @if ($overdue->isEmpty() && $upcoming->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-slate-800">You're all caught up.</p>
                    @endif
                </div>
            </div>

            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Recent submissions</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($recent as $row)
                        @php $submission = $row->submission; @endphp
                        <a href="{{ route('assignments.submit.show', $submission->assignment) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div>
                                <div class="text-[14px] font-medium">{{ $submission->assignment->title }}</div>
                                <div class="mt-0.5 text-[12.5px] text-slate-800">Submitted {{ $submission->submitted_at?->format('j M Y, H:i') }}</div>
                            </div>
                            @if ($row->label)
                                <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-sm border {{ $row->label['bg'] }} {{ $row->label['fg'] }} {{ $row->label['border'] }}">{{ $row->label['label'] }}</span>
                            @else
                                <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-sm border bg-slate-100 text-slate-900 border-slate-300">In progress</span>
                            @endif
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">Nothing submitted yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
