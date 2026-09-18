<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ renaming: {{ $errors->any() ? 'true' : 'false' }} }">
        <a href="{{ route('admin.faculties.index') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to faculties
        </a>

        <div class="mt-2.5 flex items-center gap-3" x-show="! renaming" x-transition>
            <h1 class="text-[28px] font-semibold tracking-tight">{{ $faculty->name }}</h1>
            <button @click="renaming = true" type="button" class="text-[12px] font-semibold text-navy hover:underline">
                Rename
            </button>
        </div>

        <form x-show="renaming" x-cloak x-transition method="POST" action="{{ route('admin.faculties.update', $faculty) }}" class="mt-2.5 flex items-start gap-2.5">
            @csrf
            @method('patch')
            <div>
                <x-text-input name="name" type="text" value="{{ old('name', $faculty->name) }}" required autofocus class="text-[20px] font-semibold" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>
            <x-primary-button class="mt-0.5">Save</x-primary-button>
            <button @click="renaming = false" type="button" class="mt-0.5 text-[13px] font-medium text-slate-800 hover:text-ink px-3 py-2.5">Cancel</button>
        </form>

        <div class="mt-1 text-[13px] text-slate-900">{{ $semesters->count() }} {{ Str::plural('semester', $semesters->count()) }}</div>

        <div class="mt-8 grid gap-6" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            @foreach ($semesters as $semester)
                @php
                    $teacherCount = $semester->courses->pluck('teacher_id')->filter()->unique()->count();
                @endphp
                <a href="{{ route('admin.semesters.show', $semester) }}"
                   class="bg-white border border-slate-300 rounded-sm px-7 py-6 self-start hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-4">
                        <div class="text-[19px] font-semibold tracking-tight">Semester {{ $semester->number }}</div>
                        <svg class="w-4 h-4 shrink-0 text-slate-500 mt-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01-.02-1.06L10.94 10 7.19 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.04.02z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-[20px] font-semibold tabular-nums">{{ $semester->students_count }}</div>
                            <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Student', $semester->students_count) }}</div>
                        </div>
                        <div>
                            <div class="text-[20px] font-semibold tabular-nums">{{ $teacherCount }}</div>
                            <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Teacher', $teacherCount) }}</div>
                        </div>
                        <div>
                            <div class="text-[20px] font-semibold tabular-nums">{{ $semester->courses_count }}</div>
                            <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Course', $semester->courses_count) }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
