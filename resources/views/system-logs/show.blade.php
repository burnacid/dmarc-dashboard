<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Operations</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">System log #{{ $log->id }}</h1>
                <p class="mt-2 text-sm text-slate-400">Detailed event payload and metadata.</p>
            </div>

            <a href="{{ route('system-logs.index') }}" class="self-start rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                Back to logs
            </a>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-400">Level</p>
                <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->levelBadgeClass() }}">{{ $log->level }}</span>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-400">Channel</p>
                <p class="mt-1 text-sm text-white">{{ $log->channel ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-400">Logged At</p>
                <p class="mt-1 text-sm text-white">{{ optional($log->logged_at)->format('Y-m-d H:i:s') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-400">Created At</p>
                <p class="mt-1 text-sm text-white">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-400">Level Value</p>
                <p class="mt-1 text-sm text-white">{{ $log->level_value }}</p>
            </div>
        </div>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Message</h2>
            <pre class="mt-3 whitespace-pre-wrap rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100">{{ $log->message }}</pre>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Context</h2>
            <pre class="mt-3 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-xs text-slate-100">{{ json_encode($log->context ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Extra</h2>
            <pre class="mt-3 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-xs text-slate-100">{{ json_encode($log->extra ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    </div>
</x-app-layout>

