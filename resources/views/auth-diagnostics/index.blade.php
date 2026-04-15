<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-amber-300">Diagnostics</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Auth Diagnostic Logs</h1>
                <p class="mt-2 text-sm text-slate-400">Trace login, 2FA, and passkey auth events. Enable with <code class="rounded bg-white/10 px-1 py-0.5 text-xs text-amber-200">APP_AUTH_DIAGNOSTICS_ENABLED=true</code>.</p>
            </div>

            <div class="flex items-center gap-3">
                @if (!config('app.auth_diagnostics_enabled'))
                    <span class="rounded-full border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-400">Logging disabled</span>
                @else
                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">Logging active</span>
                @endif
                <span class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300">{{ $logs->total() }} entries</span>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

        {{-- App Key Fingerprint --}}
        <div class="rounded-3xl border border-amber-400/20 bg-amber-400/5 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-300">App Key Fingerprint (current server)</p>
            <p class="mt-1 font-mono text-sm text-white">{{ $keyFingerprint }}</p>
            <p class="mt-1 text-xs text-slate-400">
                Each log entry records the fingerprint at login time. If entries show a different fingerprint, the <code class="text-amber-200">APP_KEY</code> changed and that is why remember-me cookies stopped working.
            </p>
        </div>

        {{-- Filters --}}
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <form method="GET" action="{{ route('auth-diagnostics.index') }}" class="flex flex-wrap gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="f_event" class="text-xs font-medium text-slate-400">Event prefix</label>
                    <select id="f_event" name="event" class="rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All events</option>
                        @foreach ($eventPrefixes as $prefix)
                            <option value="{{ $prefix }}" @selected($filters['event'] === $prefix)>{{ $prefix }}</option>
                        @endforeach
                        @foreach (['password', 'two_factor', 'passkey', 'event'] as $p)
                            @if (!$eventPrefixes->contains($p))
                                <option value="{{ $p }}" @selected($filters['event'] === $p)>{{ $p }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="f_level" class="text-xs font-medium text-slate-400">Level</label>
                    <select id="f_level" name="level" class="rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All levels</option>
                        <option value="info" @selected($filters['level'] === 'info')>Info</option>
                        <option value="warning" @selected($filters['level'] === 'warning')>Warning</option>
                        <option value="error" @selected($filters['level'] === 'error')>Error</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="f_user_id" class="text-xs font-medium text-slate-400">User ID</label>
                    <input id="f_user_id" name="user_id" type="number" value="{{ $filters['user_id'] }}" placeholder="e.g. 1" class="w-28 rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Filter
                    </button>
                    <a href="{{ route('auth-diagnostics.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        {{-- Log table --}}
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="overflow-x-auto rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Time</th>
                            <th class="px-4 py-3 font-medium">Event</th>
                            <th class="px-4 py-3 font-medium">Level</th>
                            <th class="px-4 py-3 font-medium">User ID</th>
                            <th class="px-4 py-3 font-medium">Remember?</th>
                            <th class="px-4 py-3 font-medium">Cookie?</th>
                            <th class="px-4 py-3 font-medium">Key FP</th>
                            <th class="px-4 py-3 font-medium">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                        @forelse ($logs as $entry)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 py-3 align-top text-xs text-slate-400 whitespace-nowrap">{{ $entry->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="font-mono text-xs text-slate-100">{{ $entry->event }}</span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $entry->levelBadgeClass() }}">{{ $entry->level }}</span>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-300">{{ $entry->user_id ?? '—' }}</td>
                                <td class="px-4 py-3 align-top text-center">
                                    @if ($entry->remember_requested !== null)
                                        <span @class(['text-emerald-400' => $entry->remember_requested, 'text-slate-500' => !$entry->remember_requested])>
                                            {{ $entry->remember_requested ? '✓' : '✗' }}
                                        </span>
                                    @elseif ($entry->remember_effective !== null)
                                        <span @class(['text-emerald-400' => $entry->remember_effective, 'text-slate-500' => !$entry->remember_effective])>
                                            {{ $entry->remember_effective ? '✓' : '✗' }}
                                        </span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-center">
                                    @if ($entry->recaller_cookie_present !== null)
                                        <span @class(['text-emerald-400' => $entry->recaller_cookie_present, 'text-slate-500' => !$entry->recaller_cookie_present])>
                                            {{ $entry->recaller_cookie_present ? '✓' : '✗' }}
                                        </span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span @class([
                                        'font-mono text-xs rounded px-1 py-0.5',
                                        'bg-emerald-400/10 text-emerald-300' => $entry->app_key_fingerprint === $keyFingerprint,
                                        'bg-rose-400/15 text-rose-300' => $entry->app_key_fingerprint !== $keyFingerprint,
                                    ])>{{ $entry->app_key_fingerprint ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('auth-diagnostics.show', $entry) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                    @if (!config('app.auth_diagnostics_enabled'))
                                        Diagnostics are disabled. Set <code class="text-amber-200">APP_AUTH_DIAGNOSTICS_ENABLED=true</code> in your <code>.env</code> to start capturing events.
                                    @else
                                        No log entries match the current filters.
                                    @endif
                                </td>
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

        {{-- Key mismatch warning --}}
        @php
            $mismatchCount = $logs->getCollection()->filter(fn ($e) => $e->app_key_fingerprint && $e->app_key_fingerprint !== $keyFingerprint)->count();
        @endphp
        @if ($mismatchCount > 0)
            <div class="rounded-3xl border border-rose-400/30 bg-rose-400/10 p-5">
                <p class="text-sm font-semibold text-rose-200">⚠ {{ $mismatchCount }} entries on this page have a different <code>APP_KEY</code> fingerprint.</p>
                <p class="mt-1 text-sm text-slate-300">This means the application key changed after those logins were issued, invalidating their remember-me cookies. Users who logged in before a key rotation will be logged out.</p>
            </div>
        @endif

        {{-- Clear logs --}}
        <section class="rounded-3xl border border-rose-400/20 bg-rose-400/5 p-6">
            <h2 class="text-sm font-semibold text-rose-300">Clear all diagnostic logs</h2>
            <p class="mt-1 text-sm text-slate-400">This permanently deletes all entries. Only do this once you've resolved the issue.</p>
            <form method="POST" action="{{ route('auth-diagnostics.destroy') }}" class="mt-4 flex items-center gap-3" onsubmit="return confirm('Delete all auth diagnostic logs?')">
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

