@props(['active' => false])

@php
$classes = $active
    ? 'inline-flex items-center border-0 border-b-2 border-navy text-[13.5px] font-semibold text-ink px-3.5 tracking-tight'
    : 'inline-flex items-center border-0 border-b-2 border-transparent text-[13.5px] font-medium text-slate-800 hover:text-ink px-3.5 tracking-tight';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
