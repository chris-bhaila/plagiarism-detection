<x-app-layout>
    <div class="pt-10 pb-20">
        <a href="{{ route('admin.students') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to students
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
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Faculty</div>
                <div class="mt-1.5 text-[18px] font-semibold font-mono">{{ $user->faculty()?->name ?? '—' }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Semester</div>
                <div class="mt-1.5 text-[18px] font-semibold tabular-nums">{{ $user->semester?->number ?? '—' }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Courses</div>
                <div class="mt-1.5 text-[18px] font-semibold tabular-nums">{{ $courses->count() }}</div>
            </div>
        </div>

        <div class="mt-9 grid gap-8" style="grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));">
            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Courses</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($courses as $course)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0">
                            <div>
                                <a href="{{ route('admin.courses.show', $course) }}" class="text-[14px] font-medium text-ink hover:text-navy hover:underline">{{ $course->name }}</a>
                                <div class="mt-0.5 text-[12px] font-mono text-slate-700">{{ $course->code }}</div>
                            </div>
                            <div class="text-[12.5px] text-slate-800 text-right whitespace-nowrap">
                                {{ $course->teacher->name ?? 'Unassigned' }}
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">Not placed in a semester with any courses yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Assignments</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($assignments as $row)
                        @php
                            $assignment = $row->assignment;
                            $submission = $row->submission;
                            $status = $row->topReport ? \App\Models\SimilarityReport::statusStyles($row->topReport->status) : null;
                        @endphp
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0">
                            <div class="min-w-0">
                                <div class="text-[14px] font-medium truncate">{{ $assignment->title }}</div>
                                <div class="mt-0.5 text-[12px] text-slate-700">{{ $assignment->course->code }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                @if ($submission)
                                    <div class="text-[12px] text-slate-800">Submitted {{ $submission->submitted_at?->format('j M, H:i') }}</div>
                                    @if ($status)
                                        <span class="mt-1 inline-block text-[10.5px] font-semibold px-1.5 py-0.5 rounded-sm border {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">
                                            {{ ucfirst($row->topReport->status) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border">Not submitted</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No assignments across this student's courses yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
