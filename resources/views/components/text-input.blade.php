@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full box-border bg-white border border-slate-500 rounded-sm px-3.5 py-2.5 text-[14.5px] text-ink placeholder:text-slate-700 focus:border-navy-light focus:outline-none focus:ring-0']) }}>
