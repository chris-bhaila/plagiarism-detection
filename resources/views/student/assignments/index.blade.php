<x-app-layout>
    <div class="pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">My Assignments</h1>

        <form method="GET" action="{{ route('assignments.index') }}" class="mt-7 flex flex-wrap gap-2.5">
            <input type="text" name="search" value="{{ $search }}" placeholder="Title or course code"
                class="flex-1 min-w-[200px] max-w-[320px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">

            <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">
                Search
            </button>

            @if ($search)
                <a href="{{ route('assignments.index') }}" class="text-[13px] font-medium text-slate-800 hover:text-ink self-center">
                    Clear
                </a>
            @endif
        </form>

        <div class="mt-5 bg-white border border-slate-300 rounded-sm">
            @forelse ($rows as $row)
                @php
                    $assignment = $row->assignment;
                    $isOverdue = ! $row->submission && $assignment->due_date && $assignment->due_date->isPast();
                @endphp
                <div class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $assignment->title }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800">
                            {{ $assignment->course->code ?? '' }}
                            @if ($assignment->due_date)
                                · Due {{ $assignment->due_date->format('j M Y, H:i') }}
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5">
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
                           class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                            Open
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">
                    {{ $search ? 'No assignments match this search.' : 'No assignments yet.' }}
                </p>
            @endforelse
        </div>
    </div>
</x-app-layout>
