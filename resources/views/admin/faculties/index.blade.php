<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ creating: {{ $errors->any() ? 'true' : 'false' }} }">

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight">Faculties</h1>
                <div class="mt-1 text-[13px] text-slate-900">{{ $faculties->count() }} {{ Str::plural('faculty', $faculties->count()) }}</div>
            </div>
            <button @click="creating = !creating" type="button"
                class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                <span x-text="creating ? 'Cancel' : '+ New faculty'"></span>
            </button>
        </div>

        <div x-show="creating" x-cloak x-transition class="mt-6 bg-white border border-slate-300 rounded-sm p-6">
            <form method="POST" action="{{ route('admin.faculties.store') }}" class="flex flex-wrap items-end gap-4">
                @csrf

                <div class="flex-1 min-w-[200px]">
                    <x-input-label for="name" value="Faculty name" />
                    <x-text-input id="name" type="text" name="name" :value="old('name')" placeholder="BCA" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <x-primary-button class="py-2.5">
                    Create faculty (with 8 semesters)
                </x-primary-button>
            </form>
        </div>

        <div class="mt-8 grid gap-6" style="grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));">
            @forelse ($faculties as $faculty)
                @php
                    $studentCount = $faculty->semesters->sum('students_count');
                    $courseCount = $faculty->semesters->sum('courses_count');
                    $teacherCount = $faculty->semesters->flatMap->courses->pluck('teacher_id')->filter()->unique()->count();
                @endphp
                <div class="bg-white border border-slate-300 rounded-sm flex flex-col">
                    <a href="{{ route('admin.faculties.show', $faculty) }}" class="flex-1 px-8 py-7 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="text-[24px] font-semibold tracking-tight">{{ $faculty->name }}</div>
                            <svg class="w-5 h-5 shrink-0 text-slate-500 mt-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01-.02-1.06L10.94 10 7.19 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.04.02z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mt-1 text-[13px] text-slate-800">{{ $faculty->semesters_count }} {{ Str::plural('semester', $faculty->semesters_count) }}</div>

                        <div class="mt-6 grid grid-cols-3 gap-4">
                            <div>
                                <div class="text-[22px] font-semibold tabular-nums">{{ $studentCount }}</div>
                                <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Student', $studentCount) }}</div>
                            </div>
                            <div>
                                <div class="text-[22px] font-semibold tabular-nums">{{ $teacherCount }}</div>
                                <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Teacher', $teacherCount) }}</div>
                            </div>
                            <div>
                                <div class="text-[22px] font-semibold tabular-nums">{{ $courseCount }}</div>
                                <div class="mt-0.5 text-[11px] font-semibold tracking-wide uppercase text-slate-800">{{ Str::plural('Course', $courseCount) }}</div>
                            </div>
                        </div>
                    </a>

                    <div class="border-t border-slate-200 px-8 py-4 flex justify-end">
                        <form method="POST" action="{{ route('admin.faculties.destroy', $faculty) }}"
                              onsubmit="return confirm('Delete {{ $faculty->name }}? This only works while it has no students or courses.');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-danger-ink bg-white hover:bg-danger-bg hover:border-danger-border">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="col-span-full px-6 py-10 text-center text-sm text-slate-800">No faculties yet — create the first one.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
