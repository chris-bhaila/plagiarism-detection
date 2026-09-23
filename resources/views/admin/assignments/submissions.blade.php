<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ selected: [] }">

        <a href="{{ route('admin.courses.show', $assignment->course) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $assignment->course->name }}
        </a>

        <div class="mt-2.5 flex items-end justify-between gap-10 flex-wrap">
            <div>
                <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">
                    {{ $assignment->course->code }} · {{ $assignment->title }}
                </div>
                <h1 class="mt-2.5 text-[30px] font-semibold tracking-tight leading-tight">{{ $assignment->title }}</h1>
                <div class="mt-3 text-[13.5px] text-slate-900 flex gap-5 flex-wrap">
                    @if ($assignment->due_date)
                        <span>Due {{ $assignment->due_date->format('j M Y, H:i') }}</span>
                    @endif
                    <span>{{ $rows->count() }} of {{ $totalCount }} shown</span>
                </div>
            </div>

            <div class="flex gap-9">
                <div>
                    <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Flagged</div>
                    <div class="text-[34px] font-semibold tracking-tight leading-tight text-danger-ink tabular-nums">{{ $flaggedCount }}</div>
                </div>
                <div>
                    <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Median score</div>
                    <div class="text-[34px] font-semibold tracking-tight leading-tight tabular-nums">{{ round($medianScore * 100) }}%</div>
                </div>
                <div>
                    <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Awaiting review</div>
                    <div class="text-[34px] font-semibold tracking-tight leading-tight tabular-nums">{{ $pendingCount }}</div>
                </div>
            </div>
        </div>

        <div class="mt-9 flex items-center gap-2.5 flex-wrap">
            @foreach ($filters as $f)
                <a href="{{ route('admin.assignments.submissions', array_merge(['assignment' => $assignment], request()->query(), ['filter' => $f['key']])) }}"
                   class="text-[13px] font-medium px-3.5 py-1.5 rounded-sm border {{ $activeFilter === $f['key'] ? 'bg-info-bg text-info-ink border-navy-light' : 'bg-white text-slate-900 border-slate-400 hover:border-slate-600' }}">
                    {{ $f['label'] }}
                </a>
            @endforeach
            <div class="ml-auto flex items-center gap-3.5" x-show="selected.length === 0" x-transition>
                <a href="{{ route('admin.assignments.submissions.export', $assignment) }}" class="text-[12.5px] font-semibold text-navy hover:underline">
                    Export CSV
                </a>
                @foreach (['release' => 'Release all', 'hide' => 'Hide all'] as $action => $label)
                    <form method="POST" action="{{ route('admin.assignments.similarity-release.bulk', $assignment) }}"
                          onsubmit="return confirm('{{ $action === 'release' ? 'Show every student their similarity status for this assignment?' : 'Hide similarity status from every student for this assignment?' }}')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="{{ $action }}">
                        <button type="submit" class="text-[12.5px] font-semibold text-navy hover:underline">{{ $label }}</button>
                    </form>
                @endforeach
            </div>

            <div x-show="selected.length > 0" x-cloak x-transition class="ml-auto flex items-center gap-2.5">
                <span class="text-[12.5px] text-slate-800" x-text="selected.length + ' selected'"></span>
                <button type="submit" form="bulk-status-form" name="status" value="confirmed"
                    class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-navy bg-navy text-white hover:bg-navy-light">
                    Confirm
                </button>
                <button type="submit" form="bulk-status-form" name="status" value="reviewed"
                    class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                    Mark reviewed
                </button>
                <button type="submit" form="bulk-status-form" name="status" value="dismissed"
                    class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-slate-900 bg-white hover:bg-slate-100">
                    Dismiss
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.assignments.submissions', $assignment) }}" class="mt-4 flex flex-wrap gap-2.5">
            <input type="hidden" name="filter" value="{{ $activeFilter }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Student name or email"
                class="flex-1 min-w-[200px] max-w-[320px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
            <select name="sort" class="bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                <option value="score" @selected($sort === 'score')>Sort: combined score</option>
                <option value="name" @selected($sort === 'name')>Sort: student name</option>
                <option value="submitted" @selected($sort === 'submitted')>Sort: newest submitted</option>
                <option value="words" @selected($sort === 'words')>Sort: word count</option>
            </select>
            <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">Apply</button>
            @if ($search || $sort !== 'score')
                <a href="{{ route('admin.assignments.submissions', ['assignment' => $assignment, 'filter' => $activeFilter]) }}" class="text-[13px] font-medium text-slate-800 hover:text-ink self-center">Clear</a>
            @endif
        </form>

        <form id="bulk-status-form" method="POST" action="{{ route('admin.similarity-reports.bulk-update-status') }}">
            @csrf
            @method('patch')
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="report_ids[]" :value="id">
            </template>
        </form>

        <div class="mt-5 bg-white border border-slate-300 rounded-sm">
            <div class="grid grid-cols-[28px_minmax(0,1.9fr)_104px_minmax(150px,1.35fr)_minmax(0,1fr)_auto] items-center gap-4 px-6 py-3 border-b border-slate-300 text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">
                <input type="checkbox"
                    @change="selected = $event.target.checked ? [{{ $rows->pluck('topReport.id')->filter()->implode(',') }}] : []"
                    class="w-[15px] h-[15px] rounded-sm accent-navy">
                <div>Student</div>
                <div>Combined</div>
                <div>Score breakdown</div>
                <div>Flag status</div>
                <div></div>
            </div>

            @forelse ($rows as $row)
                @php
                    $status = \App\Models\SimilarityReport::statusStyles($row->status);
                    $statusJustChanged = $row->topReport && in_array($row->topReport->id, session('reportStatusJustChanged', []));
                @endphp
                <div x-data="{ notesOpen: false }" class="border-b border-slate-200 last:border-b-0 animate-row-in" style="animation-delay: {{ min($loop->index * 20, 300) }}ms">
                <div class="grid grid-cols-[28px_minmax(0,1.9fr)_104px_minmax(150px,1.35fr)_minmax(0,1fr)_auto] items-center gap-4 px-6 py-4 hover:bg-slate-50">
                    @if ($row->topReport)
                        <input type="checkbox" value="{{ $row->topReport->id }}" x-model.number="selected" class="w-[15px] h-[15px] rounded-sm accent-navy">
                    @else
                        <span></span>
                    @endif
                    <div class="min-w-0">
                        <div class="text-[15px] font-medium tracking-tight flex items-center gap-2">
                            {{ $row->submission->student->name }}
                            @if ($row->hasWebMatch)
                                <span class="inline-flex items-center gap-1 text-[10.5px] font-bold tracking-wide uppercase text-info-ink bg-info-bg border border-info-border px-1.5 py-0.5 rounded-sm">
                                    🌐 Web match
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 text-[12.5px] text-slate-800 flex flex-wrap gap-x-3.5 gap-y-1">
                            <span class="font-mono whitespace-nowrap">S-{{ str_pad($row->submission->student_id, 5, '0', STR_PAD_LEFT) }}</span>
                            <span class="whitespace-nowrap">{{ $row->submission->submitted_at?->format('j M, H:i') }}</span>
                            <span class="whitespace-nowrap">{{ number_format($row->wordCount) }} words</span>
                        </div>
                    </div>

                    <div class="flex items-baseline gap-1.5 {{ $row->band['bg'] }} {{ $row->band['border'] }} border-l-[3px] px-2.5 py-1.5 rounded-sm">
                        <span class="text-[22px] font-semibold tracking-tight tabular-nums leading-none {{ $row->band['ink'] }}">{{ round($row->score * 100) }}</span>
                        <span class="text-xs font-semibold {{ $row->band['ink'] }}">%</span>
                        <span class="ml-auto text-[10px] font-bold tracking-wide uppercase {{ $row->band['ink'] }}">{{ $row->band['label'] }}</span>
                    </div>

                    <div class="grid gap-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[10.5px] font-bold tracking-wide text-slate-800 w-[30px]">LEX</span>
                            <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-navy-light" style="width: {{ round($row->lexical * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-900 w-[30px] text-right tabular-nums">{{ round($row->lexical * 100) }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10.5px] font-bold tracking-wide text-slate-800 w-[30px]">SEM</span>
                            <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-navy-lighter" style="width: {{ round($row->semantic * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-900 w-[30px] text-right tabular-nums">{{ round($row->semantic * 100) }}%</span>
                        </div>
                    </div>

                    <div @if ($statusJustChanged) x-data="{ show: false }" x-init="$nextTick(() => show = true)" @endif>
                        <span @if ($statusJustChanged) x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @endif
                            class="inline-block text-[11.5px] font-semibold px-2.5 py-1 rounded-sm border transition-colors duration-300 {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">
                            {{ $row->status ? ucfirst($row->status) : 'No match' }}
                        </span>
                        <div class="mt-1.5 text-[11.5px] text-slate-700">
                            {{ $row->matchCount }} matched {{ Str::plural('source', $row->matchCount) }}
                        </div>
                    </div>

                    <div class="text-right flex items-center justify-end gap-2">
                        <button @click="notesOpen = !notesOpen" type="button"
                            class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light whitespace-nowrap">
                            <span x-text="notesOpen ? 'Hide notes' : 'Notes{{ $row->submission->notes->count() ? ' ('.$row->submission->notes->count().')' : '' }}'"></span>
                        </button>
                        <form method="POST" action="{{ route('admin.submissions.similarity-release.update', $row->submission) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border whitespace-nowrap transition-colors duration-300 {{ $row->submission->isSimilarityReleased() ? 'border-ok-border bg-ok-bg text-ok-deep' : 'border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light' }}">
                                {{ $row->submission->isSimilarityReleased() ? 'Released ✓' : 'Release to student' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.submissions.show', $row->submission) }}"
                           class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                            View
                        </a>
                        @if ($row->topReport)
                            <a href="{{ route('admin.similarity-reports.show', ['similarityReport' => $row->topReport, 'for' => $row->submission->id]) }}"
                               class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                                Report
                            </a>
                        @endif
                    </div>
                </div>

                <div x-show="notesOpen" x-cloak x-transition class="px-6 pb-5 pt-1">
                    <div class="max-w-[560px] bg-slate-50 border border-slate-200 rounded-sm p-4">
                        @include('partials.note-thread', ['submission' => $row->submission, 'prefix' => 'admin.'])
                    </div>
                </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-slate-800 text-sm">{{ $search ? 'No submissions match this search.' : 'No submissions match this filter.' }}</div>
            @endforelse

            <div class="px-6 py-3.5 text-[12.5px] text-slate-800">Showing {{ $rows->count() }} submission(s)</div>
        </div>
    </div>
</x-app-layout>
