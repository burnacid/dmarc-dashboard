<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Report detail</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">{{ $report->org_name ?? 'DMARC report' }}</h1>
                <p class="mt-2 text-sm text-slate-400">
                    {{ $report->policy_domain ?? 'Unknown domain' }} · {{ $report->account?->name ?? 'Unknown account' }} · Report ID {{ $report->external_report_id }}
                </p>
            </div>

            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                Back
            </a>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <h2 class="text-lg font-semibold text-white">Summary</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Organization</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $report->org_name ?? '—' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Policy domain</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $report->policy_domain ?? '—' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Period begin</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ optional($report->report_begin_at)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Period end</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ optional($report->report_end_at)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Report email</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $report->email ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <h2 class="text-lg font-semibold text-white">Parsed records</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($report->records as $record)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <p class="font-medium text-white">{{ $record->header_from ?? $report->policy_domain ?? 'Unknown domain' }}</p>
                                <span class="rounded-full bg-sky-400/15 px-3 py-1 text-xs font-semibold text-sky-200">{{ number_format($record->message_count) }} msgs</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-400">Source IP: {{ $record->source_ip }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-slate-200">DKIM: {{ $record->dkim ?? '—' }}</span>
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-slate-200">SPF: {{ $record->spf ?? '—' }}</span>
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-slate-200">Disposition: {{ $record->disposition ?? '—' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                            No parsed rows stored for this report.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Original raw XML</h2>
                    <p class="mt-1 text-sm text-slate-400">Stored exactly as imported, formatted for easier inspection.</p>
                </div>
            </div>

            <pre class="mt-5 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/80 p-4 text-xs leading-6 text-slate-200"><code>{{ $formattedXml }}</code></pre>
        </div>
    </div>
</x-app-layout>

