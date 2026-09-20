@php
    $assignment = $submission->assignment;
    $status = \App\Models\SimilarityReport::statusStyles($topReport?->status);
@endphp

<div class="pt-10 pb-20">
    <a href="{{ route($prefix.'assignments.submissions', $assignment) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
        &larr; Back to {{ $assignment->title }}
    </a>

    <div class="mt-2.5 flex items-end justify-between gap-8 flex-wrap">
        <div>
            <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">{{ $assignment->course->code }} · {{ $assignment->title }}</div>
            <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight leading-tight">{{ $submission->student->name }}</h1>
            <div class="mt-2.5 text-[13.5px] text-slate-900 flex gap-5 flex-wrap">
                <span class="font-mono">S-{{ str_pad($submission->student_id, 5, '0', STR_PAD_LEFT) }}</span>
                <span>Submitted {{ $submission->submitted_at?->format('j M Y, H:i') }}</span>
                <span>{{ number_format($wordCount) }} words</span>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <form method="POST" action="{{ route($prefix.'submissions.similarity-release.update', $submission) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border whitespace-nowrap {{ $submission->isSimilarityReleased() ? 'border-ok-border bg-ok-bg text-ok-deep' : 'border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light' }}">
                    {{ $submission->isSimilarityReleased() ? 'Released ✓' : 'Release to student' }}
                </button>
            </form>
            @if ($topReport)
                <a href="{{ route($prefix.'similarity-reports.show', $topReport) }}"
                   class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                    Open similarity report
                </a>
            @endif
        </div>
    </div>

    <div class="mt-9 flex gap-8 items-start flex-wrap">
        <div class="flex-[2] min-w-[320px] basis-[520px] bg-white border border-slate-300 rounded-sm">
            <div class="px-5 py-3.5 border-b border-slate-300 text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Submitted text</div>
            <div class="p-5 text-sm leading-[1.75] text-ink whitespace-pre-line">{{ $submission->text_content }}</div>
        </div>

        <div class="flex-1 min-w-[260px] basis-[280px] grid gap-5">
            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Best match</div>
                @if ($topReport)
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-[34px] font-semibold tracking-tight leading-none tabular-nums">{{ round($topReport->combined_score * 100) }}</span>
                        <span class="text-sm font-semibold">%</span>
                        <span class="ml-auto inline-block text-[11.5px] font-semibold px-2.5 py-1 rounded-sm border {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">{{ ucfirst($topReport->status) }}</span>
                    </div>
                @else
                    <p class="mt-3 text-[13px] text-slate-800">No matching submissions — nothing to review.</p>
                @endif
            </div>

            <div class="bg-white border border-slate-300 rounded-sm p-5">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Follow-up notes</div>
                <div class="mt-3">
                    @include('partials.note-thread', ['submission' => $submission, 'prefix' => $prefix])
                </div>
            </div>
        </div>
    </div>
</div>
