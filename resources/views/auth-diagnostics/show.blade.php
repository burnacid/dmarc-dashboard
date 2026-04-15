<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-amber-300">Diagnostics</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Log entry #{{ $log->id }}</h1>
                <p class="mt-2 font-mono text-sm text-slate-400">{{ $log->event }}</p>
            </div>

            <a href="{{ route('auth-diagnostics.index') }}" class="self-start rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                ← Back to logs
            </a>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

        {{-- Key match indicator --}}
        @if ($log->app_key_fingerprint)
            @if ($log->app_key_fingerprint === $keyFingerprint)
                <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/5 p-5">
                    <p class="text-sm font-semibold text-emerald-300">✓ APP_KEY fingerprint matches current server.</p>
                    <p class="mt-1 text-xs text-slate-400 font-mono">{{ $log->app_key_fingerprint }}</p>
                </div>
            @else
                <div class="rounded-3xl border border-rose-400/30 bg-rose-400/10 p-5">
                    <p class="text-sm font-semibold text-rose-300">⚠ APP_KEY fingerprint mismatch — remember-me cookie from this login is now invalid.</p>
                    <div class="mt-2 grid grid-cols-2 gap-4 text-xs font-mono">
                        <div>
                            <p class="text-slate-400 mb-1">At login time</p>
                            <p class="text-rose-200">{{ $log->app_key_fingerprint }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 mb-1">Current server</p>
                            <p class="text-emerald-200">{{ $keyFingerprint }}</p>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $cards = [
                    ['label' => 'Level', 'value' => $log->level, 'badge' => $log->levelBadgeClass()],
                    ['label' => 'User ID', 'value' => $log->user_id ?? '—', 'badge' => null],
                    ['label' => 'Remember req.', 'value' => $log->remember_requested === null ? '—' : ($log->remember_requested ? 'Yes' : 'No'), 'badge' => null],
                    ['label' => 'Remember eff.', 'value' => $log->remember_effective === null ? '—' : ($log->remember_effective ? 'Yes' : 'No'), 'badge' => null],
                    ['label' => 'Recaller cookie', 'value' => $log->recaller_cookie_present === null ? '—' : ($log->recaller_cookie_present ? 'Present' : 'Absent'), 'badge' => null],
                    ['label' => 'Recorded at', 'value' => $log->created_at->format('H:i:s'), 'badge' => null],
                ];
            @endphp
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-4">
                    <p class="text-xs text-slate-400">{{ $card['label'] }}</p>
                    @if ($card['badge'])
                        <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $card['badge'] }}">{{ $card['value'] }}</span>
                    @else
                        <p class="mt-1 text-sm font-semibold text-white">{{ $card['value'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Full context dump --}}
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Full context</h2>
            <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/60">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-400 w-64">Key</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-400">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach (($log->context ?? []) as $key => $value)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-300 align-top">{{ $key }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-100 align-top break-all">
                                    @if (is_null($value))
                                        <span class="text-slate-500">null</span>
                                    @elseif (is_bool($value))
                                        <span @class(['text-emerald-400' => $value, 'text-slate-400' => !$value])>{{ $value ? 'true' : 'false' }}</span>
                                    @elseif (is_array($value))
                                        <span class="text-sky-300">{{ json_encode($value) }}</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>

