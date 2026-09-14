<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-navy border border-navy rounded-sm font-semibold text-[13.5px] text-white hover:bg-navy-light active:bg-[#15304c] focus:outline-none transition-colors duration-150']) }}>
    {{ $slot }}
</button>
