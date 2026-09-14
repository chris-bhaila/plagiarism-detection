<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">
        <h1 class="text-[28px] font-semibold tracking-tight">My Courses</h1>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            @forelse ($courses as $course)
                <a href="{{ route('courses.assignments.index', $course) }}"
                   class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $course->name }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800 font-mono">{{ $course->code }}</div>
                    </div>
                    <span class="text-[12.5px] text-slate-800">{{ $course->assignments()->count() }} {{ Str::plural('assignment', $course->assignments()->count()) }}</span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No courses yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
