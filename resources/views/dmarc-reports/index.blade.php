<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Archive</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">All DMARC reports</h1>
                <p class="mt-2 text-sm text-slate-400">Browse imported reports newest first, narrow the list with filters, and page through the full history.</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                {{ $reports->total() }} total report{{ $reports->total() === 1 ? '' : 's' }}
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 lg:grid-cols-8">
                <div class="space-y-2">
                    <label for="account_id" class="text-sm font-medium text-slate-200">Account</label>
                    <select id="account_id" name="account_id" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All accounts</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected($filters['account_id'] === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>


                <div class="space-y-2">
                    <label for="org" class="text-sm font-medium text-slate-200">Organization</label>
                    <input id="org" name="org" type="text" value="{{ $filters['org'] }}" placeholder="Google" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="report_id" class="text-sm font-medium text-slate-200">Report ID</label>
                    <input id="report_id" name="report_id" type="text" value="{{ $filters['report_id'] }}" placeholder="abc-123" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="range" class="text-sm font-medium text-slate-200">Time range</label>
                    <select id="range" name="range" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        @foreach ($rangeOptions as $rangeValue => $rangeLabel)
                            <option value="{{ $rangeValue }}" @selected($filters['range'] === $rangeValue)>{{ $rangeLabel }}</option>
                        @endforeach
                    </select>
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
                        @foreach ([10, 25, 50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3 lg:col-span-8">
                    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Apply filters
                    </button>
                    <a href="{{ route('reports.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
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
                            <th class="px-4 py-3 font-medium">Report</th>
                            <th class="px-4 py-3 font-medium">Account</th>
                            <th class="px-4 py-3 font-medium">Rows</th>
                            <th class="px-4 py-3 font-medium">Messages</th>
                            <th class="px-4 py-3 font-medium">SPF / DKIM</th>
                            <th class="px-4 py-3 font-medium">Reported</th>
                            <th class="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-medium text-white">{{ $report->org_name ?? 'Unknown sender' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $report->policy_domain ?? 'Unknown domain' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID {{ $report->external_report_id }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">{{ $report->account?->name ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ number_format($report->records_count) }}</td>
                                <td class="px-4 py-3 align-top">{{ number_format((int) ($report->total_messages ?? 0)) }}</td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            @if ((int) ($report->dkim_fail_messages ?? 0) > 0)
                                                <span class="rounded-full bg-rose-400/15 px-2.5 py-1 font-semibold text-rose-200">DKIM fail</span>
                                            @else
                                                <span class="rounded-full bg-emerald-400/15 px-2.5 py-1 font-semibold text-emerald-200">DKIM pass</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            @if ((int) ($report->spf_fail_messages ?? 0) > 0)
                                                <span class="rounded-full bg-rose-400/15 px-2.5 py-1 font-semibold text-rose-200">SPF fail</span>
                                            @else
                                                <span class="rounded-full bg-emerald-400/15 px-2.5 py-1 font-semibold text-emerald-200">SPF pass</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    {{ optional($report->report_end_at ?? $report->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('reports.show', $report) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Open report</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400">No reports match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="mt-6">
                    {{ $reports->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

