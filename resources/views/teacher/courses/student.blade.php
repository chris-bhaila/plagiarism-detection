<x-app-layout>
    <div class="pt-10 pb-20">
        <a href="{{ route('courses.show', $course) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $course->name }}
        </a>

        <div class="mt-2.5">
            <h1 class="text-[28px] font-semibold tracking-tight">{{ $student->name }}</h1>
            <div class="mt-1.5 text-[13px] text-slate-900">{{ $student->email }} &middot; {{ $course->code }}</div>
        </div>

        <div class="mt-9">
            <h2 class="text-[15px] font-semibold tracking-tight">Assignments</h2>
            <div class="mt-4 max-w-[720px] bg-white border border-slate-300 rounded-sm">
                @forelse ($assignments as $row)
                    @php
                        $assignment = $row->assignment;
                        $submission = $row->submission;
                        $status = $row->topReport ? \App\Models\SimilarityReport::statusStyles($row->topReport->status) : null;
                    @endphp
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0">
                        <div class="min-w-0">
                            <div class="text-[14px] font-medium truncate">{{ $assignment->title }}</div>
                            <div class="mt-0.5 text-[12px] text-slate-700">
                                @if ($assignment->due_date)
                                    Due {{ $assignment->due_date->format('j M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            @if ($submission)
                                <div class="text-[12px] text-slate-800">Submitted {{ $submission->submitted_at?->format('j M, H:i') }}</div>
                                @if ($row->topReport)
                                    <a href="{{ route('similarity-reports.show', $row->topReport) }}"
                                       class="mt-1 inline-block text-[10.5px] font-semibold px-1.5 py-0.5 rounded-sm border {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">
                                        {{ ucfirst($row->topReport->status) }}
                                    </a>
                                @endif
                            @else
                                <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border">Not submitted</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-800">No assignments in this course yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
