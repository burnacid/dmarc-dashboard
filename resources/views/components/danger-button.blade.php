<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-rose-100 transition hover:bg-rose-400/20 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-0']) }}>
    {{ $slot }}
</button>
