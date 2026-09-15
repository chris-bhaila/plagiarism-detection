<x-app-layout max-width="narrow">
    <div class="pt-10 pb-20">
        <a href="{{ route('courses.show', $assignment->course) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $assignment->course->name }}
        </a>

        <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $assignment->title }}</h1>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm p-6">
            <form method="POST" action="{{ route('assignments.update', $assignment) }}" class="grid gap-5" enctype="multipart/form-data">
                @csrf
                @method('patch')

                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" type="text" name="title" :value="old('title', $assignment->title)" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="description" value="Description (optional)" />
                    <textarea id="description" name="description" rows="4"
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">{{ old('description', $assignment->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="due_date" value="Due date (optional)" />
                        <input id="due_date" type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                        <x-input-error :messages="$errors->get('due_date')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="similarity_threshold" value="Flag threshold (0–1)" />
                        <input id="similarity_threshold" type="number" name="similarity_threshold" step="0.01" min="0" max="1" value="{{ old('similarity_threshold', $assignment->similarity_threshold) }}"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                        <x-input-error :messages="$errors->get('similarity_threshold')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="attachment" value="Attachment — .docx only" />
                    @if ($assignment->hasAttachment())
                        <div class="mt-1.5 mb-2 flex items-center justify-between gap-3 bg-slate-100 border border-slate-300 rounded-sm px-3.5 py-2.5">
                            <a href="{{ route('assignments.attachment', $assignment) }}" class="text-[13.5px] font-semibold text-navy hover:underline">{{ $assignment->attachment_name }}</a>
                            <label class="flex items-center gap-1.5 text-[12.5px] text-slate-800 cursor-pointer">
                                <input type="checkbox" name="remove_attachment" value="1" class="w-[14px] h-[14px] accent-navy">
                                Remove
                            </label>
                        </div>
                        <p class="mb-1.5 text-[12px] text-slate-700">Uploading a new file below replaces this one.</p>
                    @endif
                    <input id="attachment" type="file" name="attachment" accept=".docx"
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:bg-info-bg file:text-info-ink file:text-[13px] file:font-semibold">
                    <x-input-error :messages="$errors->get('attachment')" class="mt-1.5" />
                </div>

                <div>
                    <x-primary-button>Save</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
