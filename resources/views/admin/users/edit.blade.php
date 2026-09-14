<x-app-layout>
    <div class="max-w-[640px] mx-auto px-8 pt-10 pb-20">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <a href="{{ $editedUser->isStudent() ? route('admin.students') : route('admin.teachers') }}" class="text-[12.5px] text-slate-800 hover:text-ink">
            &larr; Back to {{ $editedUser->isStudent() ? 'students' : 'teachers' }}
        </a>

        <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $editedUser->name }}</h1>

        {{-- Status --}}
        <div class="mt-7 bg-white border border-slate-300 rounded-sm p-6 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-[11px] font-semibold tracking-wide uppercase text-slate-800">Account status</div>
                <span class="mt-1.5 inline-block text-[12.5px] font-semibold px-2.5 py-1 rounded-sm border {{ $editedUser->isDisabled() ? 'bg-danger-bg text-danger-ink border-danger-border' : 'bg-ok-bg text-ok-deep border-ok-border' }}">
                    {{ $editedUser->isDisabled() ? 'Disabled' : 'Active' }}
                </span>
            </div>

            @if ($editedUser->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.toggle-disabled', $editedUser) }}">
                    @csrf
                    @if ($editedUser->isDisabled())
                        <x-secondary-button>Re-enable account</x-secondary-button>
                    @else
                        <x-danger-button>Disable account</x-danger-button>
                    @endif
                </form>
            @else
                <p class="text-[12.5px] text-slate-700">You can't disable your own account.</p>
            @endif
        </div>

        {{-- Edit form --}}
        <div class="mt-7 bg-white border border-slate-300 rounded-sm p-6" x-data="{ role: '{{ old('role', $editedUser->role) }}' }">
            <h2 class="text-[17px] font-semibold tracking-tight">Account details</h2>

            <form method="POST" action="{{ route('admin.users.update', $editedUser) }}" class="mt-6 grid gap-5">
                @csrf
                @method('patch')

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $editedUser->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" :value="old('email', $editedUser->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" x-model="role" @if ($editedUser->id === auth()->id()) disabled @endif
                        class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink capitalize focus:border-navy-light focus:outline-none disabled:bg-slate-100 disabled:text-slate-700">
                        @foreach ([\App\Models\User::ROLE_STUDENT, \App\Models\User::ROLE_TEACHER, \App\Models\User::ROLE_ADMIN] as $role)
                            <option value="{{ $role }}" class="capitalize" @selected(old('role', $editedUser->role) === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                    @if ($editedUser->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $editedUser->role }}">
                        <p class="mt-1.5 text-[12px] text-slate-700">You can't change your own role.</p>
                    @endif
                    <x-input-error :messages="$errors->get('role')" class="mt-1.5" />
                </div>

                <div x-show="role === '{{ \App\Models\User::ROLE_STUDENT }}'" x-cloak class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="faculty" value="Faculty" />
                        <select id="faculty" name="faculty"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty }}" @selected(old('faculty', $editedUser->faculty) === $faculty)>{{ $faculty }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('faculty')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="semester" value="Semester" />
                        <select id="semester" name="semester"
                            class="w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink focus:border-navy-light focus:outline-none">
                            @for ($s = 1; $s <= 8; $s++)
                                <option value="{{ $s }}" @selected((int) old('semester', $editedUser->semester) === $s)>Semester {{ $s }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('semester')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-primary-button>Save</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
