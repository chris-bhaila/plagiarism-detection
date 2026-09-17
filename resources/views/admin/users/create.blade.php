<x-app-layout max-width="narrow">
    <div class="pt-10 pb-20">
        <a href="{{ url()->previous() }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back
        </a>

        <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">New account</h1>
        <p class="mt-1.5 text-[13px] text-slate-900">Student or teacher only — admin accounts are provisioned directly against the database.</p>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm p-6" x-data="{
                role: '{{ old('role', \App\Models\User::ROLE_STUDENT) }}',
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
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5">
                @csrf

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" name="password" type="password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirm password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" required />
                    </div>
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" x-model="role"
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink capitalize focus:border-navy-light focus:outline-none">
                        @foreach ([\App\Models\User::ROLE_STUDENT, \App\Models\User::ROLE_TEACHER] as $role)
                            <option value="{{ $role }}" class="capitalize" @selected(old('role', \App\Models\User::ROLE_STUDENT) === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1.5" />
                </div>

                <div x-show="role === '{{ \App\Models\User::ROLE_STUDENT }}'" x-cloak x-transition class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="faculty_id" value="Faculty" />
                        <select id="faculty_id" x-model.number="facultyId" @change="semesterId = null"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            <option value="">Select faculty</option>
                            <template x-for="faculty in faculties" :key="faculty.id">
                                <option :value="faculty.id" x-text="faculty.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="semester_id" value="Semester" />
                        <select id="semester_id" name="semester_id" x-model.number="semesterId"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            <option value="">Select semester</option>
                            <template x-for="semester in semesters" :key="semester.id">
                                <option :value="semester.id" x-text="'Semester ' + semester.number"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('semester_id')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-primary-button>Create account</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
