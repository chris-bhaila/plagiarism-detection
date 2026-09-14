<x-app-layout>
    <div class="max-w-[1360px] mx-auto px-8 pt-10 pb-20">
        <div class="text-xs font-semibold tracking-[0.9px] uppercase text-slate-800">{{ $course->code }}</div>
        <h1 class="mt-2.5 text-[28px] font-semibold tracking-tight">{{ $course->name }}</h1>

        <div class="mt-7 bg-white border border-slate-300 rounded-sm">
            @forelse ($assignments as $assignment)
                <div class="flex items-center justify-between gap-6 px-6 py-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
                    <div>
                        <div class="text-[15px] font-medium">{{ $assignment->title }}</div>
                        <div class="mt-1 text-[12.5px] text-slate-800">
                            @if ($assignment->due_date)
                                Due {{ $assignment->due_date->format('j M Y, H:i') }} ·
                            @endif
                            threshold {{ round($assignment->similarity_threshold * 100) }}%
                        </div>
                    </div>
                    <a href="{{ route('assignments.submissions', $assignment) }}"
                       class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                        Review submissions
                    </a>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No assignments yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
