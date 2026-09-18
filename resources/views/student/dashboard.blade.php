<x-app-layout>
    <div class="pt-10 pb-20">

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="m-0 text-[28px] font-semibold tracking-tight">Welcome back, {{ Auth::user()->name }}</h1>
                <div class="mt-2.5 text-[13.5px] text-slate-900">Your assignments across {{ $coursesCount }} {{ Str::plural('course', $coursesCount) }}</div>
            </div>
        </div>

        <div class="mt-7 grid gap-5" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Courses</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ number_format($coursesCount) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">
                    <a href="{{ route('student.courses.index') }}" class="font-semibold text-navy hover:underline">View my courses</a>
                </div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Submitted</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ number_format($submittedCount) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">of {{ number_format($totalAssignments) }} {{ Str::plural('assignment', $totalAssignments) }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Overdue</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums {{ $overdueCount > 0 ? 'text-danger-ink' : '' }}">{{ number_format($overdueCount) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">not yet submitted, past due</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">New feedback</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums {{ $unseenCount > 0 ? 'text-info-ink' : '' }}">{{ number_format($unseenCount) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">notes or released statuses to check</div>
            </div>
        </div>

        <div class="mt-11 grid gap-11 items-start" style="grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));">

            <div>
                <h2 class="m-0 text-[17px] font-semibold tracking-tight">Due soon</h2>
                <div class="mt-2 text-[13px] text-slate-900">Not-yet-submitted assignments, soonest due first</div>

                <div class="mt-6 bg-white border border-slate-300 rounded-sm">
                    @forelse ($dueSoonRows as $row)
                        @php $isOverdue = $row->assignment->due_date && $row->assignment->due_date->isPast(); @endphp
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="text-[13.5px] font-medium">{{ $row->assignment->title }}</div>
                                <div class="mt-0.5 text-xs text-slate-700">
                                    {{ $row->assignment->course->code ?? '' }}
                                    @if ($row->assignment->due_date)
                                        &middot; {{ $isOverdue ? 'Was due' : 'Due' }} {{ $row->assignment->due_date->format('j M Y, H:i') }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                @if ($isOverdue)
                                    <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-deep border border-danger-border">Overdue</span>
                                @endif
                                <a href="{{ route('assignments.submit.show', $row->assignment) }}"
                                   class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light whitespace-nowrap">
                                    Open
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-800">Nothing due — you're all caught up.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="m-0 text-[17px] font-semibold tracking-tight">Recent feedback</h2>
                <div class="mt-2 text-[13px] text-slate-900">Notes or similarity statuses you haven't seen yet</div>

                <div class="mt-6 bg-white border border-slate-300 rounded-sm">
                    @forelse ($unseenRows as $row)
                        <a href="{{ route('assignments.submit.show', $row->assignment) }}"
                           class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="text-[13.5px] font-medium">{{ $row->assignment->title }}</div>
                                <div class="mt-0.5 text-xs text-slate-700">{{ $row->assignment->course->code ?? '' }}</div>
                            </div>
                            <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-info-bg text-info-ink border border-navy-light whitespace-nowrap">
                                {{ $row->submission->unseenActivitySummary() }}
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-800">No new feedback right now.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
