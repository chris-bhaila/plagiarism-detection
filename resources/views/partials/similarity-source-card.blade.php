{{-- One side of a student-vs-student similarity report. Shared by
     teacher/admin similarity-reports/show.blade.php — $prefix picks the
     route family ('' or 'admin.'), same convention as note-thread. --}}
<div class="bg-white border border-slate-300 rounded-sm">
    <div class="px-5 py-3.5 border-b border-slate-300 flex justify-between items-baseline">
        <div>
            <div class="text-[14.5px] font-semibold">{{ $submission->student->name }}</div>
            <div class="mt-0.5 text-xs text-slate-800 font-mono">
                S-{{ str_pad($submission->student_id, 5, '0', STR_PAD_LEFT) }} · submitted {{ $submission->submitted_at?->format('j M H:i') }}
            </div>
        </div>
        <span class="text-[11.5px] font-semibold text-slate-800">SOURCE {{ $side }}</span>
    </div>
    <div class="p-5 text-sm leading-[1.75] text-ink">
        {!! $report->highlight($submission->text_content) !!}
    </div>

    <div x-data="{ notesOpen: false }" class="border-t border-slate-200 px-5 py-3.5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <button @click="notesOpen = !notesOpen" type="button" class="text-[12.5px] font-semibold text-navy hover:underline">
                <span x-text="notesOpen ? 'Hide notes' : 'Notes{{ $submission->notes->count() ? ' ('.$submission->notes->count().')' : '' }} for {{ $submission->student->name }}'"></span>
            </button>
            <form method="POST" action="{{ route($prefix.'submissions.similarity-release.update', $submission) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="text-[12.5px] font-semibold {{ $submission->isSimilarityReleased() ? 'text-ok-deep' : 'text-navy' }} hover:underline">
                    {{ $submission->isSimilarityReleased() ? 'Released to student ✓ — unrelease' : 'Release status to student' }}
                </button>
            </form>
        </div>

        <div x-show="notesOpen" x-cloak x-transition class="mt-3">
            @include('partials.note-thread', ['submission' => $submission, 'prefix' => $prefix])
        </div>
    </div>
</div>
