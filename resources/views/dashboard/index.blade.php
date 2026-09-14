<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="m-0 text-[28px] font-semibold tracking-tight">Integrity analytics</h1>
                <div class="mt-2.5 text-[13.5px] text-slate-900">Across all courses · updated just now</div>
            </div>
        </div>

        <div class="mt-7 grid gap-5" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Total submissions</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ number_format($totalSubmissions) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">across {{ number_format($totalAssignments) }} {{ Str::plural('assignment', $totalAssignments) }}</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Flagged for review</div>
                <div class="mt-3.5 flex items-baseline gap-2.5">
                    <span class="text-[44px] font-semibold tracking-tight leading-none tabular-nums text-danger-ink">{{ number_format($flaggedCount) }}</span>
                    <span class="text-[15px] font-medium text-danger-ink">{{ $flaggedPct }}%</span>
                </div>
                <div class="mt-3 text-[12.5px] text-slate-900">{{ $pendingCount }} awaiting a decision</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Average similarity</div>
                <div class="mt-3.5 flex items-baseline gap-1.5">
                    <span class="text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ $avgCombinedPct }}</span>
                    <span class="text-xl font-semibold">%</span>
                </div>
                <div class="mt-3 text-[12.5px] text-slate-900">lexical {{ $avgLexicalPct }}% · semantic {{ $avgSemanticPct }}%</div>
            </div>
            <div class="bg-white border border-slate-300 rounded-sm p-6">
                <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Confirmed concerns</div>
                <div class="mt-3.5 text-[44px] font-semibold tracking-tight leading-none tabular-nums">{{ number_format($confirmedCount) }}</div>
                <div class="mt-3 text-[12.5px] text-slate-900">{{ $confirmedPctOfFlagged }}% of flags, {{ $dismissedCount }} dismissed</div>
            </div>
        </div>

        <div class="mt-11 grid gap-11 items-start" style="grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));">

            <div>
                <h2 class="m-0 text-[17px] font-semibold tracking-tight">What drove each flag</h2>
                <div class="mt-2 text-[13px] text-slate-900">{{ $flaggedCount }} flags by dominant signal, per course</div>
                <div class="mt-3 flex gap-5 text-xs">
                    <span class="flex items-center gap-1.5 text-slate-900"><span class="w-2.5 h-2.5 bg-navy-light inline-block"></span>Lexical-driven</span>
                    <span class="flex items-center gap-1.5 text-slate-900"><span class="w-2.5 h-2.5 bg-navy-lighter inline-block"></span>Semantic-driven</span>
                    <span class="flex items-center gap-1.5 text-slate-900"><span class="w-2.5 h-2.5 bg-slate-500 inline-block"></span>Both</span>
                </div>

                <div class="mt-6 grid gap-5">
                    @forelse ($bars as $bar)
                        <div>
                            <div class="flex justify-between items-baseline text-[13px]">
                                <span class="font-medium">{{ $bar->label }}</span>
                                <span class="text-slate-800 tabular-nums">{{ $bar->total }} {{ Str::plural('flag', $bar->total) }}</span>
                            </div>
                            <div class="mt-2 flex h-[18px] rounded-sm overflow-hidden bg-slate-200">
                                <div class="bg-navy-light" style="width: {{ $bar->lexPct }}%"></div>
                                <div class="bg-navy-lighter" style="width: {{ $bar->semPct }}%"></div>
                                <div class="bg-slate-500" style="width: {{ $bar->bothPct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-800">No flagged reports yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="m-0 text-[17px] font-semibold tracking-tight">Most flagged</h2>
                <div class="mt-2 text-[13px] text-slate-900">Assignments with the highest flag rate</div>
                <div class="mt-6 bg-white border border-slate-300 rounded-sm">
                    <div class="grid grid-cols-[minmax(0,1fr)_74px_80px_90px] gap-3.5 px-5 py-2.5 border-b border-slate-300 text-[11px] font-semibold tracking-wide uppercase text-slate-800">
                        <div>Assignment</div>
                        <div class="text-right">Subs</div>
                        <div class="text-right">Flagged</div>
                        <div class="text-right">Avg score</div>
                    </div>
                    @forelse ($topAssignments as $row)
                        <div class="grid grid-cols-[minmax(0,1fr)_74px_80px_90px] gap-3.5 px-5 py-3 border-b border-slate-200 items-baseline hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="text-[13.5px] font-medium">{{ $row->name }}</div>
                                <div class="mt-0.5 text-xs text-slate-700">{{ $row->course }}</div>
                            </div>
                            <div class="text-right text-[13.5px] tabular-nums text-slate-900">{{ $row->subs }}</div>
                            <div class="text-right text-[13.5px] font-semibold tabular-nums">{{ $row->flagged }}</div>
                            <div class="text-right">
                                <span class="text-[13.5px] font-semibold tabular-nums {{ $row->band['ink'] }} {{ $row->band['bg'] }} px-2 py-0.5 rounded-sm">{{ $row->avg }}%</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-sm text-slate-800">No assignments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
