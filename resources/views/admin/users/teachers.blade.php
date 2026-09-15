<x-app-layout>
    <div class="pt-10 pb-20">
        <div class="flex items-end justify-between gap-6 flex-wrap">
            <div>
                <h1 class="text-[28px] font-semibold tracking-tight">Teachers</h1>
                <div class="mt-1 text-[13px] text-slate-900">{{ $teachers->count() }} {{ Str::plural('teacher', $teachers->count()) }}</div>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="text-[12.5px] font-semibold px-3.5 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                + New account
            </a>
        </div>

        <form method="GET" action="{{ route('admin.teachers') }}" class="mt-7 flex flex-wrap gap-2.5">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name or email"
                class="flex-1 min-w-[200px] max-w-[320px] bg-white border border-slate-500 rounded-sm px-3 py-2 text-[13.5px] text-ink focus:border-navy-light focus:outline-none">

            <button type="submit" class="bg-navy hover:bg-navy-light border border-navy rounded-sm text-white text-[13.5px] font-semibold px-4 py-2">
                Search
            </button>

            @if ($filters['search'])
                <a href="{{ route('admin.teachers') }}" class="text-[13px] font-medium text-slate-800 hover:text-ink self-center">
                    Clear
                </a>
            @endif
        </form>

        <div class="mt-5 bg-white border border-slate-300 rounded-sm">
            <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1.4fr)_130px_90px] gap-4 px-6 py-3 border-b border-slate-300 text-[11px] font-semibold tracking-wide uppercase text-slate-800">
                <div>Name</div>
                <div>Email</div>
                <div class="text-right">Courses taught</div>
                <div></div>
            </div>

            @forelse ($teachers as $teacher)
                <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1.4fr)_130px_90px] gap-4 px-6 py-4 border-b border-slate-200 last:border-b-0 items-center hover:bg-slate-50">
                    <div class="text-[14.5px] font-medium flex items-center gap-2">
                        <a href="{{ route('admin.users.show', $teacher) }}" class="text-ink hover:text-navy hover:underline">{{ $teacher->name }}</a>
                        @if ($teacher->isDisabled())
                            <span class="text-[10px] font-bold tracking-wide uppercase px-1.5 py-0.5 rounded-sm bg-danger-bg text-danger-ink border border-danger-border">Disabled</span>
                        @endif
                    </div>
                    <div class="text-[13px] text-slate-800">{{ $teacher->email }}</div>
                    <div class="text-right text-[14px] font-medium tabular-nums">{{ $teacher->courses_taught_count }}</div>
                    <div class="text-right">
                        <a href="{{ route('admin.users.edit', $teacher) }}" class="text-[12.5px] font-semibold px-3 py-1.5 rounded-sm border border-slate-500 text-navy bg-white hover:bg-info-bg hover:border-navy-light">
                            Edit
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-800">No teachers match this search.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
