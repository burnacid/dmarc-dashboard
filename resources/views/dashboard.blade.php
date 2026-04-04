<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Overview</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">DMARC monitoring dashboard</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Trends shown for the last {{ strtolower($range['label']) }}. Use the range and failure filters to zoom into suspicious activity.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3"></div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Configured accounts</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['total_accounts'] }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Active accounts</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['active_accounts'] }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Imported reports</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ $stats['total_reports'] }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm text-slate-400">Last poll</p>
                <p class="mt-3 text-xl font-semibold text-white">{{ $stats['last_polled_at'] ?? 'Never polled' }}</p>
            </div>
        </section>

        <section x-data="{ showCustomTime: {{ $range['is_custom'] ? 'true' : 'false' }} }" class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Message trend over time</h2>
                    <p class="mt-1 text-sm text-slate-400">Default range is one month. Failed traffic is highlighted so you can zoom into problem periods.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($rangeOptions as $value => $label)
                        @continue($value === 'custom')
                        <a
                            href="{{ route('dashboard', array_filter(['range' => $value, 'focus' => $focus, 'domain' => $selectedDomain])) }}"
                            class="rounded-2xl px-3 py-2 text-sm font-medium transition {{ $range['value'] === $value ? 'bg-sky-400 text-slate-950' : 'border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach

                    <button
                        type="button"
                        x-on:click="showCustomTime = !showCustomTime"
                        x-bind:aria-expanded="showCustomTime.toString()"
                        class="rounded-2xl px-3 py-2 text-sm font-medium transition {{ $range['is_custom'] ? 'bg-sky-400 text-slate-950' : 'border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10' }}"
                    >
                        <span x-text="showCustomTime ? 'Hide custom time' : 'Custom time'"></span>
                    </button>
                </div>
            </div>

            <form x-cloak x-show="showCustomTime" x-transition method="GET" action="{{ route('dashboard') }}" class="mt-4 grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 md:grid-cols-[1fr,1fr,auto] md:items-end">
                    <input type="hidden" name="focus" value="{{ $focus }}">
                    <input type="hidden" name="range" value="custom">
                    @if ($selectedDomain !== '')
                        <input type="hidden" name="domain" value="{{ $selectedDomain }}">
                    @endif

                    <div>
                        <label for="from" class="text-xs uppercase tracking-[0.18em] text-slate-500">From</label>
                        <input id="from" name="from" type="date" value="{{ $range['from_input'] }}" class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                    </div>

                    <div>
                        <label for="to" class="text-xs uppercase tracking-[0.18em] text-slate-500">To</label>
                        <input id="to" name="to" type="date" value="{{ $range['to_input'] }}" class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                    </div>

                    <button type="submit" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Apply
                    </button>
            </form>

            @php
                $trendMax = max(1, (int) ($timeSeries->max('total_messages') ?? 1));
            @endphp

            <div class="mt-6 overflow-x-auto">
                <div class="flex min-w-[760px] items-end gap-3">
                    @foreach ($timeSeries as $point)
                        @php
                            $totalHeight = max(6, (int) round(($point->total_messages / $trendMax) * 220));
                            $failHeight = $point->total_messages > 0
                                ? max(4, (int) round(($point->failed_messages / $trendMax) * 220))
                                : 0;
                            $reportLink = route('reports.index', array_filter([
                                'range' => 'custom',
                                'from' => $point->key,
                                'to' => $point->key,
                                'domain' => $selectedDomain,
                            ], fn ($value) => $value !== ''));
                        @endphp
                        <div class="flex w-full min-w-[18px] flex-1 flex-col items-center gap-2">
                            <div class="text-[10px] text-slate-500">{{ number_format($point->total_messages) }}</div>
                            <a
                                href="{{ $reportLink }}"
                                class="relative flex h-[220px] w-full items-end justify-center rounded-t-2xl bg-white/5 px-1 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300"
                                aria-label="View reports for {{ $point->key }}"
                                title="View reports for {{ $point->key }}"
                            >
                                <div class="w-full rounded-t-xl bg-sky-400/35" style="height: {{ $totalHeight }}px"></div>
                                @if ($point->failed_messages > 0)
                                    <div class="absolute bottom-0 w-[70%] rounded-t-xl bg-rose-400" style="height: {{ $failHeight }}px"></div>
                                @endif
                            </a>
                            <div class="text-center text-[10px] text-slate-400 tabular-nums">{{ $point->label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-slate-400">
                <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-sky-400/35"></span> Total messages</div>
                <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-rose-400"></span> Failed messages</div>
                <div class="text-slate-500">Tip: click a bar to open reports for that day.</div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Records per domain</h2>
                        <p class="mt-1 text-sm text-slate-400">Volume grouped by `header_from` with report domain fallback.</p>
                    </div>
                </div>

                @php
                    $domainMax = max(1, (int) ($domainVolumes->max('total_messages') ?? 1));
                @endphp

                <div class="mt-5 space-y-4">
                    @forelse ($domainVolumes as $domain)
                        @php
                            $percentage = min(100, (int) round(($domain->total_messages / $domainMax) * 100));
                            $domainReportsLink = route('reports.index', array_merge($rangeQuery, ['domain' => $domain->domain]));
                        @endphp
                        <a
                            href="{{ $domainReportsLink }}"
                            title="View reports for {{ $domain->domain }}"
                            aria-label="View reports for {{ $domain->domain }}"
                            class="block rounded-2xl px-2 py-2 transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300"
                        >
                            <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                <p class="font-medium text-white">{{ $domain->domain }}</p>
                                <p class="text-slate-300">{{ number_format((int) $domain->total_messages) }} msgs</p>
                            </div>
                            <div class="h-2.5 rounded-full bg-white/10">
                                <div class="h-2.5 rounded-full bg-sky-400" style="width: {{ $percentage }}%"></div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                            No domain data yet.
                        </div>
                    @endforelse
                </div>

                <p class="mt-4 text-xs text-slate-500">Click a domain row to open matching reports.</p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <h2 class="text-lg font-semibold text-white">Authentication results</h2>
                <p class="mt-1 text-sm text-slate-400">DKIM/SPF pass-fail totals and policy disposition outcomes for the selected range.</p>

                @php
                    $dkimPass = (int) ($resultSummary->dkim_pass ?? 0);
                    $dkimFail = (int) ($resultSummary->dkim_fail ?? 0);
                    $spfPass = (int) ($resultSummary->spf_pass ?? 0);
                    $spfFail = (int) ($resultSummary->spf_fail ?? 0);
                    $dispositionNone = (int) ($resultSummary->disposition_none ?? 0);
                    $dispositionQuarantine = (int) ($resultSummary->disposition_quarantine ?? 0);
                    $dispositionReject = (int) ($resultSummary->disposition_reject ?? 0);
                    $dispositionOther = (int) ($resultSummary->disposition_other ?? 0);
                    $authMax = max(1, $dkimPass, $dkimFail, $spfPass, $spfFail);
                    $dispositionTotal = max(1, $dispositionNone + $dispositionQuarantine + $dispositionReject + $dispositionOther);
                @endphp

                <div class="mt-5 space-y-4">
                    @foreach ([
                        ['label' => 'DKIM pass', 'value' => $dkimPass, 'bar' => 'bg-emerald-400'],
                        ['label' => 'DKIM fail', 'value' => $dkimFail, 'bar' => 'bg-rose-400'],
                        ['label' => 'SPF pass', 'value' => $spfPass, 'bar' => 'bg-emerald-400'],
                        ['label' => 'SPF fail', 'value' => $spfFail, 'bar' => 'bg-rose-400'],
                    ] as $line)
                        @php $percent = min(100, (int) round(($line['value'] / $authMax) * 100)); @endphp
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <p class="text-slate-200">{{ $line['label'] }}</p>
                                <p class="text-slate-300">{{ number_format($line['value']) }}</p>
                            </div>
                            <div class="h-2.5 rounded-full bg-white/10">
                                <div class="h-2.5 rounded-full {{ $line['bar'] }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-white/10 pt-5">
                    <p class="text-sm font-medium text-slate-200">Policy disposition split</p>
                    <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-white/10">
                        <div class="bg-sky-400" style="width: {{ ($dispositionNone / $dispositionTotal) * 100 }}%"></div>
                        <div class="bg-amber-400" style="width: {{ ($dispositionQuarantine / $dispositionTotal) * 100 }}%"></div>
                        <div class="bg-rose-400" style="width: {{ ($dispositionReject / $dispositionTotal) * 100 }}%"></div>
                        <div class="bg-slate-500" style="width: {{ ($dispositionOther / $dispositionTotal) * 100 }}%"></div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-300">
                        <p>None: {{ number_format($dispositionNone) }}</p>
                        <p>Quarantine: {{ number_format($dispositionQuarantine) }}</p>
                        <p>Reject: {{ number_format($dispositionReject) }}</p>
                        <p>Other: {{ number_format($dispositionOther) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Failure drill-down</h2>
                        <p class="mt-1 text-sm text-slate-400">Zoom into failed traffic, inspect what failed, and open the original report XML.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($focusOptions as $value => $label)
                            <a
                                href="{{ route('dashboard', array_merge($rangeQuery, ['focus' => $value])) }}"
                                class="rounded-2xl px-3 py-2 text-sm font-medium transition {{ $focus === $value ? 'bg-rose-400 text-white' : 'border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-5">
                    @foreach ($focusOptions as $value => $label)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-2xl font-semibold text-white">{{ number_format((int) ($failureSummary[$value] ?? 0)) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5 text-left text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">Domain / source</th>
                                <th class="px-4 py-3 font-medium">What failed</th>
                                <th class="px-4 py-3 font-medium">Messages</th>
                                <th class="px-4 py-3 font-medium">Reported</th>
                                <th class="px-4 py-3 font-medium">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                            @forelse ($focusedFailures as $row)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-medium text-white">{{ $row->domain }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ $row->source_ip }} · {{ $row->account_name }}</p>
                                        @if ($row->org_name)
                                            <p class="mt-1 text-xs text-slate-500">{{ $row->org_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap gap-2">
                                            @if (strtolower((string) $row->dkim) === 'fail')
                                                <span class="rounded-full bg-rose-400/15 px-2.5 py-1 text-xs font-semibold text-rose-200">DKIM fail</span>
                                            @endif
                                            @if (strtolower((string) $row->spf) === 'fail')
                                                <span class="rounded-full bg-amber-400/15 px-2.5 py-1 text-xs font-semibold text-amber-200">SPF fail</span>
                                            @endif
                                            @if (in_array(strtolower((string) $row->disposition), ['quarantine', 'reject'], true))
                                                <span class="rounded-full bg-sky-400/15 px-2.5 py-1 text-xs font-semibold text-sky-200">Disposition {{ strtolower((string) $row->disposition) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">{{ number_format($row->message_count) }}</td>
                                    <td class="px-4 py-3 align-top">{{ $row->reported_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <a href="{{ route('reports.show', $row->report_id) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">View original report</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">No failed records in this range for the current filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-white">Recent DMARC reports</h2>
                            <p class="mt-1 text-sm text-slate-400">Latest parsed reports inside the selected timeframe.</p>
                        </div>
                        <a href="{{ route('reports.index', $rangeQuery) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Browse all reports</a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentReports as $report)
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-white">{{ $report->org_name ?? 'Unknown sender' }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ $report->policy_domain ?? '—' }} · {{ $report->account?->name ?? '—' }}</p>
                                    </div>
                                    <a href="{{ route('reports.show', $report) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Open</a>
                                </div>
                                <p class="mt-3 text-xs text-slate-500">{{ optional($report->report_end_at)->format('Y-m-d H:i') ?? '—' }}</p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                                No DMARC reports imported yet for this range.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                    <h2 class="text-lg font-semibold text-white">Noisiest source IPs</h2>
                    <p class="mt-1 text-sm text-slate-400">Top message counts aggregated from imported DMARC records.</p>

                    <div class="mt-5 space-y-3">
                        @forelse ($topSources as $source)
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="font-medium text-white">{{ $source->source_ip }}</p>
                                    <span class="rounded-full bg-sky-400/15 px-3 py-1 text-xs font-semibold text-sky-200">{{ number_format($source->total_messages) }} msgs</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                                No record rows available yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-white">Account health</h2>
                            <p class="mt-1 text-sm text-slate-400">Quick status view for the inboxes you are polling.</p>
                        </div>
                        <a href="{{ route('imap-accounts.create') }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Add account</a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($accounts as $account)
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-white">{{ $account->name }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ $account->username }} · {{ $account->host }}:{{ $account->port }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-400/15 text-emerald-200' : 'bg-slate-400/10 text-slate-300' }}">
                                        {{ $account->is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-sm text-slate-400">
                                    <span>{{ $account->reports_count }} report(s)</span>
                                    <span>{{ $account->last_polled_at?->diffForHumans() ?? 'Never polled' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                                No IMAP accounts configured yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
