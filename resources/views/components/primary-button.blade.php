@props(['loading' => null])

<button
    @if ($loading) x-bind:disabled="{{ $loading }}" @endif
    {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-navy border border-navy rounded-sm font-semibold text-[13.5px] text-white hover:bg-navy-light active:bg-[#15304c] focus:outline-none disabled:opacity-70 disabled:cursor-not-allowed transition-colors duration-150']) }}
>
    @if ($loading)
        <svg x-show="{{ $loading }}" class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @endif
    <span @if ($loading) x-show="!({{ $loading }})" @endif>{{ $slot }}</span>
</button>
