<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Operations</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">System Logs</h1>
                <p class="mt-2 text-sm text-slate-400">Browse framework and application logs written to the database.</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                {{ $logs->total() }} total entr{{ $logs->total() === 1 ? 'y' : 'ies' }}
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <form method="GET" action="{{ route('system-logs.index') }}" class="grid gap-4 lg:grid-cols-6">
                <div class="space-y-2">
                    <label for="channel" class="text-sm font-medium text-slate-200">Channel</label>
                    <select id="channel" name="channel" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All channels</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel }}" @selected($filters['channel'] === $channel)>{{ $channel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="level" class="text-sm font-medium text-slate-200">Level</label>
                    <select id="level" name="level" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All levels</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 lg:col-span-2">
                    <label for="q" class="text-sm font-medium text-slate-200">Message contains</label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="error, queue, login..." class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="from" class="text-sm font-medium text-slate-200">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="to" class="text-sm font-medium text-slate-200">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="per_page" class="text-sm font-medium text-slate-200">Per page</label>
                    <select id="per_page" name="per_page" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        @foreach ([25, 50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3 lg:col-span-5">
                    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Apply filters
                    </button>
                    <a href="{{ route('system-logs.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="overflow-x-auto rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Logged</th>
                            <th class="px-4 py-3 font-medium">Level</th>
                            <th class="px-4 py-3 font-medium">Channel</th>
                            <th class="px-4 py-3 font-medium">Message</th>
                            <th class="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-3 align-top whitespace-nowrap text-xs text-slate-400">{{ optional($log->logged_at)->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->levelBadgeClass() }}">{{ $log->level }}</span>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-300">{{ $log->channel ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <p class="max-w-3xl truncate" title="{{ $log->message }}">{{ $log->message }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('system-logs.show', $log) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">No system logs match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="mt-6">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>

        <section class="rounded-3xl border border-rose-400/20 bg-rose-400/5 p-6">
            <h2 class="text-sm font-semibold text-rose-300">Clear all system logs</h2>
            <p class="mt-1 text-sm text-slate-400">This permanently deletes all entries in <code class="text-rose-200">system_logs</code>.</p>
            <form method="POST" action="{{ route('system-logs.destroy') }}" class="mt-4 flex items-center gap-3" onsubmit="return confirm('Delete all system logs?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm" value="DELETE">
                <button type="submit" class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-400/20">
                    Clear logs
                </button>
            </form>
        </section>
    </div>
</x-app-layout>

