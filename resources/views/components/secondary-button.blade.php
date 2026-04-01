<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-slate-100 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
