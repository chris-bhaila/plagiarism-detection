<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ creating: {{ $errors->any() ? 'true' : 'false' }} }">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">{{ $course->code }}</div>
                <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $course->name }}</h1>
                <p class="mt-1.5 text-[13px] text-slate-900">{{ $course->semester->label() }}</p>
            </div>
        </div>

        <div class="mt-9 grid gap-8" style="grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));">
            <div>
                <div class="flex items-end justify-between gap-4 flex-wrap">
                    <h2 class="text-[15px] font-semibold tracking-tight">Assignments</h2>
                    <button @click="creating = !creating" type="button"
                        class="text-[12px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                        <span x-text="creating ? 'Cancel' : '+ New assignment'"></span>
                    </button>
                </div>

                <div x-show="creating" x-cloak x-transition class="mt-4 bg-white border border-slate-300 rounded-sm p-6">
                    <form method="POST" action="{{ route('assignments.store') }}" class="grid gap-5" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">

                        <div>
                            <x-input-label for="title" value="Title" />
                            <x-text-input id="title" type="text" name="title" :value="old('title')" placeholder="Essay: The History of the Internet" required autofocus />
                            <x-input-error :messages="$errors->get('title')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Description (optional)" />
                            <textarea id="description" name="description" rows="4"
                                class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="due_date" value="Due date (optional)" />
                                <input id="due_date" type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                                    class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                                <x-input-error :messages="$errors->get('due_date')" class="mt-1.5" />
                            </div>

                            <div>
                                <x-input-label for="similarity_threshold" value="Flag threshold (0–1, default 0.35)" />
                                <input id="similarity_threshold" type="number" name="similarity_threshold" step="0.01" min="0" max="1" value="{{ old('similarity_threshold') }}" placeholder="0.35"
                                    class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                                <x-input-error :messages="$errors->get('similarity_threshold')" class="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="attachment" value="Attachment — .docx only (optional)" />
                            <input id="attachment" type="file" name="attachment" accept=".docx"
                                class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:bg-info-bg file:text-info-ink file:text-[13px] file:font-semibold">
                            <x-input-error :messages="$errors->get('attachment')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-primary-button>Create assignment</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($assignments as $assignment)
                        <div class="flex items-center justify-between gap-6 px-5 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div>
                                <div class="text-[14.5px] font-medium">{{ $assignment->title }}</div>
                                <div class="mt-1 text-[12.5px] text-slate-800 flex items-center gap-2 flex-wrap">
                                    @if ($assignment->due_date)
                                        <span>Due {{ $assignment->due_date->format('j M Y, H:i') }} &middot;</span>
                                    @endif
                                    <span>threshold {{ round($assignment->similarity_threshold * 100) }}%</span>
                                    @if ($assignment->hasAttachment())
                                        <span>&middot;</span>
                                        <a href="{{ route('assignments.attachment', $assignment) }}" class="font-semibold text-navy hover:underline">{{ $assignment->attachment_name }}</a>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('assignments.submissions', $assignment) }}"
                                   class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light whitespace-nowrap">
                                    Review submissions
                                </a>
                                <a href="{{ route('assignments.edit', $assignment) }}"
                                   class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('assignments.destroy', $assignment) }}"
                                      onsubmit="return confirm('Delete \'{{ $assignment->title }}\'? This also deletes its submissions and similarity reports.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-danger-ink bg-white hover:bg-danger-bg hover:border-danger-border">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No assignments yet.</p>
                    @endforelse
                </div>
            </div>

            <div x-data="{
                    q: '',
                    matches(haystack) { return haystack.includes(this.q.toLowerCase()); },
                }">
                <h2 class="text-[15px] font-semibold tracking-tight">Enrolled students</h2>
                <div class="mt-1 text-[13px] text-slate-900">{{ $roster->count() }} {{ Str::plural('student', $roster->count()) }} — automatically enrolled via {{ $course->semester->label() }}</div>

                @if ($roster->count() > 5)
                    <input type="text" x-model="q" placeholder="Filter by name or email"
                        class="mt-3 w-full max-w-[320px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                @endif

                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($roster as $student)
                        <a href="{{ route('courses.students.show', [$course, $student]) }}"
                           x-show="matches(@js(Str::lower($student->name.' '.$student->email)))" x-cloak
                           x-transition.opacity.duration.100ms
                           class="flex items-center justify-between gap-4 px-5 py-3 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                            <div>
                                <div class="text-[14px] font-medium text-ink">{{ $student->name }}</div>
                                <div class="mt-0.5 text-[12px] text-slate-700">{{ $student->email }}</div>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No students in this semester yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
