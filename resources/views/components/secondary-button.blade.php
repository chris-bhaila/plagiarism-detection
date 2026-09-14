<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-white border border-slate-500 rounded-sm font-semibold text-[13.5px] text-navy hover:bg-info-bg hover:border-navy-light focus:outline-none disabled:opacity-40 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
