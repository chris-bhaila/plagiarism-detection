@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-[12.5px] text-danger-ink space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
