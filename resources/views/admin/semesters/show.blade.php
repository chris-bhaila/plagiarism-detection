<x-app-layout>
    <div class="pt-10 pb-20" x-data="{ addingCourse: {{ $errors->any() ? 'true' : 'false' }} }">
        <a href="{{ route('admin.faculties.show', $semester->faculty) }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $semester->faculty->name }}
        </a>

        @if (session('status'))
            <div class="mt-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-2.5 flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight">{{ $semester->label() }}</h1>
                <div class="mt-1 text-[13px] text-slate-900">
                    {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}
                    &middot; {{ $teachers->count() }} {{ Str::plural('teacher', $teachers->count()) }}
                    &middot; {{ $students->count() }} {{ Str::plural('student', $students->count()) }}
                </div>
            </div>
            <button @click="addingCourse = !addingCourse" type="button"
                class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                <span x-text="addingCourse ? 'Cancel' : '+ New course'"></span>
            </button>
        </div>

        <div x-show="addingCourse" x-cloak x-transition class="mt-6 bg-white border border-slate-300 rounded-sm p-6">
            <form method="POST" action="{{ route('admin.courses.store') }}" class="flex flex-wrap items-end gap-4">
                @csrf
                <input type="hidden" name="semester_id" value="{{ $semester->id }}">

                <div class="flex-1 min-w-[200px]">
                    <x-input-label for="name" value="Course name" />
                    <x-text-input id="name" type="text" name="name" :value="old('name')" placeholder="Introduction to Computer Science" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div class="w-[160px]">
                    <x-input-label for="code" value="Course code" />
                    <x-text-input id="code" type="text" name="code" :value="old('code')" placeholder="CS101" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1.5" />
                </div>

                <div class="w-[220px]">
                    <x-input-label for="teacher_id" value="Teacher (optional)" />
                    <select id="teacher_id" name="teacher_id"
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                        <option value="" @selected(old('teacher_id') === null)>Unassigned</option>
                        @foreach ($allTeachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((int) old('teacher_id') === $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('teacher_id')" class="mt-1.5" />
                </div>

                <x-primary-button class="py-2.5">
                    Add course
                </x-primary-button>
            </form>
        </div>

        <div class="mt-8 grid gap-8" style="grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));">
            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Courses</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($courses as $course)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0">
                            <div>
                                <a href="{{ route('admin.courses.show', $course) }}" class="text-[14px] font-medium text-ink hover:text-navy hover:underline">{{ $course->name }}</a>
                                <div class="mt-0.5 text-[12px] font-mono text-slate-700">{{ $course->code }}</div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                @if ($course->teacher)
                                    <span class="text-[12.5px] text-slate-800 text-right whitespace-nowrap">{{ $course->teacher->name }}</span>
                                @else
                                    <span class="text-[11px] font-semibold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-warn-bg text-warn-deep border border-warn-border whitespace-nowrap">Unassigned</span>
                                @endif
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-[12px] font-semibold text-navy hover:underline">Edit</a>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No courses yet.</p>
                    @endforelse
                </div>

                <h2 class="mt-8 text-[15px] font-semibold tracking-tight">Teachers</h2>
                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($teachers as $teacher)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-200 last:border-b-0">
                            <div class="text-[14px] font-medium">{{ $teacher->name }}</div>
                            <div class="text-[12.5px] text-slate-800">{{ $teacher->email }}</div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No teachers assigned yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="text-[15px] font-semibold tracking-tight">Students</h2>
                <div class="mt-1 text-[13px] text-slate-900">{{ $students->count() }} {{ Str::plural('student', $students->count()) }} — automatically placed here</div>

                <div class="mt-4 bg-white border border-slate-300 rounded-sm">
                    @forelse ($students as $student)
                        <div class="flex items-center justify-between gap-4 px-5 py-3 border-b border-slate-200 last:border-b-0">
                            <div>
                                <div class="text-[14px] font-medium">{{ $student->name }}</div>
                                <div class="mt-0.5 text-[12px] text-slate-700">{{ $student->email }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No students placed in this semester yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
