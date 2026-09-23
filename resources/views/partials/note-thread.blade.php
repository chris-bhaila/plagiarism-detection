@php
    $viewer = auth()->user();
@endphp

@forelse ($submission->notes as $note)
    @php $canManage = $viewer->isAdmin() || $note->author_id === $viewer->id; @endphp
    <div x-data="{ editing: false }" class="pb-3 mb-3 border-b border-slate-200 last:border-b-0 last:pb-0 last:mb-0">
        <div x-show="!editing">
            <div class="text-[13px] text-ink leading-[1.6] whitespace-pre-line">{{ $note->body }}</div>
            <div class="mt-1 text-[11.5px] text-slate-700 flex flex-wrap items-center gap-x-2">
                <span>{{ $note->author->name ?? 'Deleted account' }} &middot; {{ $note->created_at->format('j M Y, H:i') }}@if ($note->updated_at->gt($note->created_at)) &middot; edited @endif</span>
                @if ($canManage)
                    <button type="button" @click="editing = true" class="font-semibold text-navy hover:underline">Edit</button>
                    <form method="POST" action="{{ route($prefix.'submissions.notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-semibold text-danger-ink hover:underline">Delete</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($canManage)
            <form x-show="editing" x-cloak method="POST" action="{{ route($prefix.'submissions.notes.update', $note) }}" class="flex items-start gap-2">
                @csrf
                @method('PATCH')
                <textarea name="body" rows="2" required
                    class="flex-1 box-border bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13px] text-ink focus:border-navy-light focus:outline-none">{{ $note->body }}</textarea>
                <x-primary-button class="py-2">Save</x-primary-button>
                <button type="button" @click="editing = false" class="text-[12.5px] font-medium text-slate-800 hover:text-ink py-2">Cancel</button>
            </form>
        @endif
    </div>
@empty
    <p class="text-[12.5px] text-slate-700">No notes yet.</p>
@endforelse

<form method="POST" action="{{ route($prefix.'submissions.notes.store', $submission) }}" class="mt-3 flex items-start gap-2">
    @csrf
    <textarea name="body" rows="2" placeholder="Add a follow-up note for {{ $submission->student->name }}…" required
        class="flex-1 box-border bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13px] text-ink focus:border-navy-light focus:outline-none"></textarea>
    <x-primary-button class="py-2">Add</x-primary-button>
</form>
