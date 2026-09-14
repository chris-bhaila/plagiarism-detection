<x-app-layout>
    <div class="max-w-[760px] mx-auto px-8 pt-14 pb-24">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        @if ($submission)
            {{-- Submitted: receipt state --}}
            <div class="bg-white border border-slate-300 rounded-sm p-11">
                <div class="w-10 h-10 rounded-full bg-ok-bg grid place-items-center">
                    <svg class="w-4 h-4 text-ok-deep" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 10l4 4 8-8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h1 class="mt-5 text-[26px] font-semibold tracking-tight">Submission received</h1>
                <p class="mt-3 max-w-[54ch] text-[14.5px] leading-[1.7] text-slate-900">
                    Your assignment was submitted successfully. Your instructor will release your similarity report once it has been reviewed.
                </p>

                <div class="mt-8 grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                    <div>
                        <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Receipt</div>
                        <div class="mt-1.5 text-sm font-mono">IC-{{ str_pad($submission->student_id, 5, '0', STR_PAD_LEFT) }}-A{{ $assignment->id }}</div>
                    </div>
                    <div>
                        <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Submitted</div>
                        <div class="mt-1.5 text-sm">{{ $submission->submitted_at?->format('j M, H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Length</div>
                        <div class="mt-1.5 text-sm">{{ number_format(str_word_count(strip_tags($submission->text_content))) }} words</div>
                    </div>
                    <div>
                        <div class="text-[11.5px] font-semibold tracking-wide uppercase text-slate-800">Check status</div>
                        <div class="mt-1.5 text-sm text-warn-deep">In progress</div>
                    </div>
                </div>

                <div class="mt-9 flex gap-3">
                    <a href="{{ route('assignments.submit.show', ['assignment' => $assignment, 'revise' => 1]) }}"
                       class="bg-white border border-slate-500 rounded-sm text-navy text-[13.5px] font-semibold px-4 py-2.5 hover:bg-info-bg hover:border-navy-light">
                        Submit a revised attempt
                    </a>
                    <a href="{{ route('assignments.index') }}" class="text-slate-900 text-[13.5px] font-medium px-4 py-2.5 hover:text-ink">
                        Back to assignments
                    </a>
                </div>
            </div>
        @else
            {{-- Not submitted: form state --}}
            <div x-data="{
                    words: 0,
                    ack: true,
                    get canSubmit() { return this.ack && this.words > 0 },
                    updateWords(text) {
                        const trimmed = text.trim();
                        this.words = trimmed ? trimmed.split(/\s+/).length : 0;
                    },
                 }">
                <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">
                    {{ $assignment->course->code }} · {{ $assignment->title }}
                </div>
                <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $assignment->title }}</h1>
                <div class="mt-3.5 flex gap-7 flex-wrap text-[13.5px] text-slate-900">
                    @if ($assignment->due_date)
                        <span>Due <strong class="font-semibold text-ink">{{ $assignment->due_date->format('j M Y, H:i') }}</strong></span>
                    @endif
                </div>

                @if ($assignment->description)
                    <p class="mt-7 max-w-[60ch] text-[14.5px] leading-[1.7] text-slate-900">{{ $assignment->description }}</p>
                @endif

                <p class="mt-4 max-w-[60ch] text-[13px] leading-[1.7] text-slate-800">
                    Your text is checked against institutional submissions and other sources. A similarity score is not a finding of misconduct — your instructor reviews every flagged report before any decision.
                </p>

                <form method="POST" action="{{ route('assignments.submit', $assignment) }}" class="mt-10 grid gap-6">
                    @csrf

                    <div>
                        <div class="flex justify-between items-baseline mb-2">
                            <label for="text_content" class="text-[13px] font-semibold">Assignment text</label>
                            <span class="text-[12.5px] text-slate-800 tabular-nums" x-text="words.toLocaleString() + ' words'"></span>
                        </div>
                        <textarea id="text_content" name="text_content" rows="12"
                            x-init="updateWords($el.value)"
                            @input="updateWords($event.target.value)"
                            placeholder="Paste or type your assignment text here."
                            class="w-full box-sizing-border bg-white border border-slate-500 rounded-sm p-3.5 font-sans text-[14.5px] leading-[1.7] text-ink focus:border-navy-light focus:outline-none"
                            required>{{ old('text_content') }}</textarea>
                        <x-input-error :messages="$errors->get('text_content')" class="mt-2" />
                    </div>

                    <label for="ack" class="flex gap-2.5 items-start text-[13.5px] leading-[1.6] text-slate-900 cursor-pointer">
                        <input id="ack" type="checkbox" x-model="ack" class="mt-0.5 w-[15px] h-[15px] accent-navy">
                        <span>This is my own work and all sources are cited in line with the academic integrity policy.</span>
                    </label>

                    <div class="flex items-center gap-4">
                        <button type="submit" :disabled="!canSubmit"
                            :class="canSubmit ? 'bg-navy hover:bg-navy-light cursor-pointer opacity-100' : 'bg-slate-600 cursor-not-allowed opacity-70'"
                            class="border-none rounded-sm text-white text-[14.5px] font-semibold px-5 py-3">
                            Submit assignment
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
