<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-danger-ink border border-danger-ink rounded-sm font-semibold text-[13.5px] text-white hover:bg-danger-deep focus:outline-none transition-colors duration-150']) }}>
    {{ $slot }}
</button>
