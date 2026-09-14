<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20" x-data="{ creating: {{ $errors->any() ? 'true' : 'false' }} }">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <h1 class="text-[28px] font-semibold tracking-tight">My Courses</h1>
            <button @click="creating = !creating" type="button"
                class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                <span x-text="creating ? 'Cancel' : '+ New course'"></span>
            </button>
        </div>

        <div x-show="creating" x-cloak x-transition class="mt-6 bg-white border border-slate-300 rounded-sm p-6">
            <form method="POST" action="{{ route('courses.store') }}" class="flex flex-wrap items-end gap-4">
                @csrf

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

                <x-primary-button class="py-2.5">
                    Create course
                </x-primary-button>
            </form>
        </div>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            @forelse ($courses as $course)
                <a href="{{ route('courses.show', $course) }}"
                   class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $course->name }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800 font-mono">{{ $course->code }}</div>
                    </div>
                    <span class="text-[12.5px] text-slate-800 text-right">
                        {{ $course->students()->count() }} {{ Str::plural('student', $course->students()->count()) }}
                        &middot;
                        {{ $course->assignments()->count() }} {{ Str::plural('assignment', $course->assignments()->count()) }}
                    </span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No courses yet — create your first one above.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
