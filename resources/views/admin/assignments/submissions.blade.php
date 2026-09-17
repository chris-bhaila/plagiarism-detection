<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ selected: [] }">

        <a href="{{ route('admin.courses.show', $assignment->course) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $assignment->course->name }}
        </a>

        @if (session('status'))
            <div class="mt-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

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
                    <span>{{ $rows->count() }} of {{ $rows->count() }} shown</span>
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
                <a href="{{ route('admin.assignments.submissions', ['assignment' => $assignment, 'filter' => $f['key']]) }}"
                   class="text-[13px] font-medium px-3.5 py-1.5 rounded-sm border {{ $activeFilter === $f['key'] ? 'bg-info-bg text-info-ink border-navy-light' : 'bg-white text-slate-900 border-slate-400 hover:border-slate-600' }}">
                    {{ $f['label'] }}
                </a>
            @endforeach
            <div class="ml-auto flex items-center gap-3.5" x-show="selected.length === 0" x-transition>
                <a href="{{ route('admin.assignments.submissions.export', $assignment) }}" class="text-[12.5px] font-semibold text-navy hover:underline">
                    Export CSV
                </a>
                <span class="text-[12.5px] text-slate-800">Sorted by combined score, descending</span>
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
                @php $status = \App\Models\SimilarityReport::statusStyles($row->status); @endphp
                <div x-data="{ notesOpen: false }" class="border-b border-slate-200 last:border-b-0">
                <div class="grid grid-cols-[28px_minmax(0,1.9fr)_104px_minmax(150px,1.35fr)_minmax(0,1fr)_auto] items-center gap-4 px-6 py-4 hover:bg-slate-50">
                    @if ($row->topReport)
                        <input type="checkbox" value="{{ $row->topReport->id }}" x-model.number="selected" class="w-[15px] h-[15px] rounded-sm accent-navy">
                    @else
                        <span></span>
                    @endif
                    <div class="min-w-0">
                        <div class="text-[15px] font-medium tracking-tight">{{ $row->submission->student->name }}</div>
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

                    <div>
                        <span class="inline-block text-[11.5px] font-semibold px-2.5 py-1 rounded-sm border {{ $status['bg'] }} {{ $status['fg'] }} {{ $status['border'] }}">
                            {{ $row->status ? ucfirst($row->status) : 'No match' }}
                        </span>
                        <div class="mt-1.5 text-[11.5px] text-slate-700">
                            {{ $row->matchCount }} matched {{ Str::plural('submission', $row->matchCount) }}
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
                                class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border whitespace-nowrap {{ $row->submission->isSimilarityReleased() ? 'border-ok-border bg-ok-bg text-ok-deep' : 'border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light' }}">
                                {{ $row->submission->isSimilarityReleased() ? 'Released ✓' : 'Release to student' }}
                            </button>
                        </form>
                        @if ($row->topReport)
                            <a href="{{ route('admin.similarity-reports.show', $row->topReport) }}"
                               class="inline-block text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                                Report
                            </a>
                        @endif
                    </div>
                </div>

                <div x-show="notesOpen" x-cloak x-transition class="px-6 pb-5 pt-1">
                    <div class="max-w-[560px] bg-slate-50 border border-slate-200 rounded-sm p-4">
                        @forelse ($row->submission->notes as $note)
                            <div class="pb-3 mb-3 border-b border-slate-200 last:border-b-0 last:pb-0 last:mb-0">
                                <div class="text-[13px] text-ink leading-[1.6]">{{ $note->body }}</div>
                                <div class="mt-1 text-[11.5px] text-slate-700">{{ $note->author->name }} &middot; {{ $note->created_at->format('j M Y, H:i') }}</div>
                            </div>
                        @empty
                            <p class="text-[12.5px] text-slate-700">No notes yet.</p>
                        @endforelse

                        <form method="POST" action="{{ route('admin.submissions.notes.store', $row->submission) }}" class="mt-3 flex items-start gap-2">
                            @csrf
                            <textarea name="body" rows="2" placeholder="Add a follow-up note for this student…" required
                                class="flex-1 box-border bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13px] text-ink focus:border-navy-light focus:outline-none"></textarea>
                            <x-primary-button class="py-2">Add</x-primary-button>
                        </form>
                    </div>
                </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-slate-800 text-sm">No submissions match this filter.</div>
            @endforelse

            <div class="px-6 py-3.5 text-[12.5px] text-slate-800">Showing {{ $rows->count() }} submission(s)</div>
        </div>
    </div>
</x-app-layout>
