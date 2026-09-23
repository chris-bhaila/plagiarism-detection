@php
    $threshold = $report->submissionA->assignment->similarity_threshold;
    $band = \App\Models\SimilarityReport::scoreBand($report->combined_score, $threshold);
    $status = \App\Models\SimilarityReport::statusStyles($report->status);
    $statusJustChanged = in_array($report->id, session('reportStatusJustChanged', []));
@endphp

<x-app-layout>
    <div class="pt-8 pb-20">

        <div class="text-[12.5px] text-slate-800">
            <a href="{{ route('assignments.submissions', $report->submissionA->assignment) }}">
                {{ $report->submissionA->assignment->course->code }} · {{ $report->submissionA->assignment->title }}
            </a>
            <span class="mx-2">/</span>
            <span>Similarity report SR-{{ str_pad($report->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        <div class="mt-6 flex flex-wrap gap-7 items-start">

            <div class="flex-1 min-w-[320px] basis-[620px]">
                <div class="flex items-end justify-between gap-6 flex-wrap">
                    <div>
                        <h1 class="m-0 text-2xl font-semibold tracking-tight">
                            {{ $report->submissionA->student->name }}
                            <span class="text-slate-600 font-normal">&nbsp;vs&nbsp;</span>
                            @if ($report->isWebSource())
                                web source
                            @else
                                {{ $report->submissionB->student->name }}
                            @endif
                        </h1>
                        <div class="mt-2 text-[13px] text-slate-900">
                            {{ $matchedPassages }} matched {{ Str::plural('passage', $matchedPassages) }}
                            @if ($totalWords > 0)
                                · {{ number_format($matchedWords) }} of {{ number_format($totalWords) }} words implicated
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-4 text-xs items-center">
                        <span class="flex items-center gap-1.5 text-slate-900">
                            <span class="w-3.5 h-3.5 bg-[#cfe0f0] border-b-2 border-[#2a5c8f] inline-block"></span>Lexical match
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-900">
                            <span class="w-3.5 h-3.5 bg-[#dfe9e6] border-b-2 border-[#4e8478] inline-block"></span>Semantic match
                        </span>
                    </div>
                </div>

                <div class="mt-5 grid gap-5" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
                    @include('partials.similarity-source-card', ['submission' => $report->submissionA, 'side' => 'A', 'report' => $report, 'prefix' => ''])
                    @if ($report->isWebSource())
                        @include('partials.similarity-web-source-card', ['report' => $report])
                    @else
                        @include('partials.similarity-source-card', ['submission' => $report->submissionB, 'side' => 'B', 'report' => $report, 'prefix' => ''])
                    @endif
                </div>
            </div>

            <div class="flex-1 min-w-[260px] basis-[280px] sticky top-[84px] grid gap-5">

                <div class="bg-white border border-slate-300 rounded-sm p-5">
                    <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Combined similarity</div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-[56px] font-semibold tracking-tight leading-none tabular-nums {{ $band['ink'] }}">{{ round($report->combined_score * 100) }}</span>
                        <span class="text-xl font-semibold {{ $band['ink'] }}">%</span>
                        <span class="ml-auto text-[11.5px] font-bold tracking-wide uppercase {{ $band['ink'] }} {{ $band['bg'] }} px-2 py-1 rounded-sm">{{ $band['label'] }}</span>
                    </div>

                    <div class="mt-6 grid gap-4.5">
                        <div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-[13px] font-medium">Lexical match</span>
                                <span class="text-[17px] font-semibold tabular-nums">{{ round($report->lexical_score * 100) }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-navy-light" style="width: {{ round($report->lexical_score * 100) }}%"></div>
                            </div>
                            <div class="mt-1.5 text-xs text-slate-800">Verbatim and near-verbatim strings</div>
                        </div>
                        <div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-[13px] font-medium">Semantic match</span>
                                <span class="text-[17px] font-semibold tabular-nums">{{ round($report->semantic_score * 100) }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-navy-lighter" style="width: {{ round($report->semantic_score * 100) }}%"></div>
                            </div>
                            <div class="mt-1.5 text-xs text-slate-800">Paraphrase and restructured argument</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-300 rounded-sm p-5">
                    <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Flag status</div>
                    <div class="mt-2.5 flex items-center gap-2.5"
                        @if ($statusJustChanged) x-data="{ show: false }" x-init="$nextTick(() => show = true)" @endif>
                        <span @if ($statusJustChanged) x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @endif
                            class="inline-block text-[13px] font-semibold px-2.5 py-1 rounded-sm border transition-colors duration-300 {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">
                            {{ ucfirst($report->status) }}
                        </span>
                    </div>

                    <div class="mt-4.5 grid gap-2">
                        <form method="POST" action="{{ route('similarity-reports.update-status', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="w-full text-left bg-navy border border-navy rounded-sm text-white text-[13.5px] font-semibold px-3.5 py-2.5 hover:bg-navy-light">
                                Confirm misconduct concern
                            </button>
                        </form>
                        <form method="POST" action="{{ route('similarity-reports.update-status', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="reviewed">
                            <button type="submit" class="w-full text-left bg-white border border-slate-500 rounded-sm text-navy text-[13.5px] font-semibold px-3.5 py-2.5 hover:bg-info-bg hover:border-navy-light">
                                Mark as reviewed
                            </button>
                        </form>
                        <form method="POST" action="{{ route('similarity-reports.update-status', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="dismissed">
                            <button type="submit" class="w-full text-left bg-transparent border border-transparent rounded-sm text-slate-900 text-[13.5px] font-medium px-3.5 py-2.5 hover:bg-slate-100 hover:text-ink">
                                Dismiss — no concern
                            </button>
                        </form>
                    </div>
                    <div class="mt-4 text-xs leading-relaxed text-slate-700">Status changes are visible to other teachers on this course.</div>
                </div>

                @if ($otherMatches->isNotEmpty())
                    <div class="px-5">
                        <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Other matches</div>
                        <div class="mt-3 grid gap-2.5">
                            @foreach ($otherMatches as $match)
                                <div class="flex justify-between text-[13px]">
                                    <span>{{ $match->label }}</span>
                                    <span class="font-semibold tabular-nums">{{ round($match->score * 100) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
