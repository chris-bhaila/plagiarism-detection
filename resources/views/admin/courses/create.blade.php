<x-app-layout max-width="narrow">
    <div class="pt-10 pb-20">
        <a href="{{ route('admin.courses.index') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to courses
        </a>

        <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">New course</h1>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm p-6">
            <form method="POST" action="{{ route('admin.courses.store') }}" class="grid gap-5" x-data="{
                    faculties: @js($faculties->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'semesters' => $f->semesters->map(fn ($s) => ['id' => $s->id, 'number' => $s->number])])),
                    facultyId: null,
                    semesterId: {{ old('semester_id') ? (int) old('semester_id') : 'null' }},
                    get semesters() {
                        const faculty = this.faculties.find(f => f.id === this.facultyId);
                        return faculty ? faculty.semesters : [];
                    },
                }" x-init="
                    if (semesterId) {
                        const f = faculties.find(f => f.semesters.some(s => s.id === semesterId));
                        if (f) facultyId = f.id;
                    }
                ">
                @csrf

                <div>
                    <x-input-label for="name" value="Course name" />
                    <x-text-input id="name" type="text" name="name" :value="old('name')" placeholder="Introduction to Computer Science" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="code" value="Course code" />
                    <x-text-input id="code" type="text" name="code" :value="old('code')" placeholder="CS101" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="faculty_id" value="Faculty" />
                        <select id="faculty_id" x-model.number="facultyId" @change="semesterId = null" required
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            <option value="" disabled>Select faculty</option>
                            <template x-for="faculty in faculties" :key="faculty.id">
                                <option :value="faculty.id" x-text="faculty.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="semester_id" value="Semester" />
                        <select id="semester_id" name="semester_id" x-model.number="semesterId" required
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            <option value="" disabled>Select semester</option>
                            <template x-for="semester in semesters" :key="semester.id">
                                <option :value="semester.id" x-text="'Semester ' + semester.number"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('semester_id')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="teacher_id" value="Teacher (optional — assign later if unsure)" />
                    <select id="teacher_id" name="teacher_id"
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                        <option value="" @selected(old('teacher_id') === null)>Unassigned</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((int) old('teacher_id') === $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('teacher_id')" class="mt-1.5" />
                </div>

                <div>
                    <x-primary-button>Create course</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
