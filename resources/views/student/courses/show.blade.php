<x-app-layout>
    <div class="pt-10 pb-20">
        <a href="{{ route('student.courses.index') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; My Courses
        </a>

        <div class="mt-2.5 flex items-end justify-between gap-6 flex-wrap">
            <div>
                <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">{{ $course->code }}</div>
                <h1 class="mt-1 text-[28px] font-semibold tracking-tight">{{ $course->name }}</h1>
                <div class="mt-2 text-[13px] text-slate-900 flex items-center gap-2">
                    <span>{{ $course->semester->label() }}</span>
                    <span>&middot;</span>
                    @if ($course->teacher)
                        <span>{{ $course->teacher->name }}</span>
                    @else
                        <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border">Unassigned</span>
                    @endif
                </div>
            </div>
        </div>

        <h2 class="mt-9 text-[15px] font-semibold tracking-tight">Assignments</h2>

        <div class="mt-4 bg-white border border-slate-300 rounded-sm">
            @forelse ($rows as $row)
                @php
                    $assignment = $row->assignment;
                    $isOverdue = ! $row->submission && $assignment->due_date && $assignment->due_date->isPast();
                @endphp
                <div class="flex items-start justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0">
                    <div class="min-w-0">
                        <div class="text-[15px] font-medium">{{ $assignment->title }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800">
                            @if ($assignment->due_date)
                                Due {{ $assignment->due_date->format('j M Y, H:i') }}
                            @else
                                No due date
                            @endif
                        </div>
                        @if ($assignment->description)
                            <p class="mt-2 max-w-[60ch] text-[13px] leading-[1.6] text-slate-900">{{ Str::limit($assignment->description, 180) }}</p>
                        @endif
                        @if ($assignment->hasAttachment())
                            <a href="{{ route('assignments.attachment', $assignment) }}"
                               class="mt-2 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-navy hover:underline">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3zM4.5 15.5a.75.75 0 01.75-.75h9.5a.75.75 0 010 1.5h-9.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                                </svg>
                                {{ $assignment->attachment_name }}
                            </a>
                        @endif
                    </div>
                    <div class="flex items-center gap-3.5 shrink-0">
                        @if ($row->submission && $row->submission->hasUnseenActivity())
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-info-bg text-info-ink border border-navy-light" title="Your instructor left a note or released a similarity status since you last checked">New</span>
                        @endif
                        @if ($row->submission)
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-ok-bg text-ok-deep border border-ok-border">Submitted</span>
                        @elseif ($isOverdue)
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-deep border border-danger-border">Overdue</span>
                        @else
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-900 border border-slate-300">Not submitted</span>
                        @endif
                        <a href="{{ route('assignments.submit.show', $assignment) }}"
                           class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light whitespace-nowrap">
                            Open
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No assignments in this course yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
