@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-[13.5px] font-medium text-ok-deep bg-ok-bg border border-ok-border rounded-sm px-3.5 py-2.5']) }}>
        {{ $status }}
    </div>
@endif
