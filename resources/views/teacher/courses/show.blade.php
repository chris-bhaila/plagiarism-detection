<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">

        @if (session('status'))
            <div class="mb-6 text-sm text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-4 py-2.5">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">{{ $course->code }}</div>
                <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $course->name }}</h1>
            </div>
            <a href="{{ route('courses.assignments.index', $course) }}"
               class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                Manage assignments
            </a>
        </div>

        <div class="mt-10 grid gap-10" style="grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));">

            {{-- Roster --}}
            <div>
                <h2 class="text-[17px] font-semibold tracking-tight">Enrolled students</h2>
                <div class="mt-1 text-[13px] text-slate-900">{{ $roster->count() }} {{ Str::plural('student', $roster->count()) }}</div>

                <div class="mt-5 bg-white border border-slate-300 rounded-sm">
                    @forelse ($roster as $student)
                        <div class="flex items-center justify-between gap-4 px-5 py-3 border-b border-slate-200 last:border-b-0">
                            <div>
                                <div class="text-[14px] font-medium">{{ $student->name }}</div>
                                <div class="mt-0.5 text-[12px] text-slate-700">{{ $student->email }}</div>
                            </div>
                            <div class="text-[12.5px] text-slate-800 text-right">
                                @if ($student->faculty)
                                    <span class="font-mono">{{ $student->faculty }}</span>
                                @endif
                                @if ($student->semester)
                                    &middot; Sem {{ $student->semester }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-800">No students enrolled yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Enroll students --}}
            <div>
                <h2 class="text-[17px] font-semibold tracking-tight">Enroll students</h2>
                <div class="mt-1 text-[13px] text-slate-900">Search and filter to find students, then select and enroll.</div>

                <form method="GET" action="{{ route('courses.show', $course) }}" class="mt-5 flex flex-wrap gap-2.5">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name or email"
                        class="flex-1 min-w-[160px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">

                    <select name="faculty" class="bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                        <option value="">Any faculty</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty }}" @selected($filters['faculty'] === $faculty)>{{ $faculty }}</option>
                        @endforeach
                    </select>

                    <select name="semester" class="bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">
                        <option value="">Any semester</option>
                        @for ($s = 1; $s <= 8; $s++)
                            <option value="{{ $s }}" @selected($filters['semester'] === $s)>Semester {{ $s }}</option>
                        @endfor
                    </select>

                    <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">
                        Search
                    </button>
                </form>

                @if ($hasSearched)
                    <form method="POST" action="{{ route('courses.enroll', $course) }}" class="mt-5">
                        @csrf

                        <div class="bg-white border border-slate-300 rounded-sm">
                            @forelse ($candidates as $student)
                                <label class="flex items-center gap-3 px-5 py-3 border-b border-slate-200 last:border-b-0 cursor-pointer hover:bg-slate-50">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="w-[15px] h-[15px] rounded-sm accent-navy">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[14px] font-medium">{{ $student->name }}</div>
                                        <div class="mt-0.5 text-[12px] text-slate-700">{{ $student->email }}</div>
                                    </div>
                                    <div class="text-[12.5px] text-slate-800 text-right whitespace-nowrap">
                                        @if ($student->faculty)
                                            <span class="font-mono">{{ $student->faculty }}</span>
                                        @endif
                                        @if ($student->semester)
                                            &middot; Sem {{ $student->semester }}
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-slate-800">No matching students found.</p>
                            @endforelse
                        </div>

                        @if ($candidates->isNotEmpty())
                            <button type="submit" class="mt-4 bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2.5">
                                Enroll selected
                            </button>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
