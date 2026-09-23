{{-- The web-source side of a similarity report (source_type: 'web').
     Shared by teacher/admin similarity-reports/show.blade.php. Shows the
     specific matched passage the similarity check identified, not just a
     link — this is what lets a teacher see why the match was flagged
     without leaving the page, same purpose as the shingle highlighting on
     the student-vs-student side. --}}
<div class="bg-white border border-slate-300 rounded-sm">
    <div class="px-5 py-3.5 border-b border-slate-300 flex justify-between items-baseline gap-4">
        <div class="min-w-0">
            <div class="text-[14.5px] font-semibold truncate">{{ $report->source_title ?: 'Web source' }}</div>
            @if ($report->source_url)
                <a href="{{ $report->source_url }}" target="_blank" rel="noopener noreferrer"
                   class="mt-0.5 text-xs text-navy hover:underline break-all">
                    {{ $report->source_url }}
                </a>
            @endif
        </div>
        <span class="text-[11.5px] font-semibold text-slate-800 whitespace-nowrap">SOURCE B · WEB</span>
    </div>

    <div class="px-5 pt-3.5 text-[11.5px] text-slate-700 italic">
        This page may have changed since it was checked.
    </div>

    <div class="p-5 text-sm leading-[1.75] text-ink">
        @if ($report->matched_web_passage)
            {!! $report->highlight($report->matched_web_passage) !!}
        @else
            <p class="text-slate-700 text-[13px]">No specific passage was captured for this match — the page-level score above is all that's available.</p>
        @endif
    </div>
</div>
