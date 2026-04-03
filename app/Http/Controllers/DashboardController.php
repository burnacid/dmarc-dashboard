<?php

namespace App\Http\Controllers;

use App\Models\DmarcRecord;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Services\Dmarc\DmarcIngestionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $range = $this->resolveRange($request);
        $focus = $this->resolveFocus($request->string('focus')->toString());
        $selectedDomain = trim((string) $request->input('domain', (string) $request->session()->get('filters.domain', '')));

        $domainOptions = DB::table('dmarc_records')
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $user->id)
            ->selectRaw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain) as domain")
            ->whereNotNull(DB::raw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain)"))
            ->distinct()
            ->orderBy('domain')
            ->pluck('domain')
            ->filter(fn ($domain) => is_string($domain) && trim($domain) !== '')
            ->values();

        if ($selectedDomain !== '' && ! $domainOptions->contains($selectedDomain)) {
            $selectedDomain = '';
        }

        $request->session()->put('filters.domain', $selectedDomain);

        $rangeQuery = $this->rangeQuery($range, $selectedDomain);

        $accounts = $user->imapAccounts()
            ->withCount('reports')
            ->latest()
            ->get();

        $recentReports = DmarcReport::query()
            ->with('account:id,name,user_id')
            ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
            ->when($selectedDomain !== '', fn ($query) => $query->where('policy_domain', $selectedDomain))
            ->where(function ($query) use ($range): void {
                $query
                    ->whereBetween('report_end_at', [$range['start'], $range['end']])
                    ->orWhere(function ($orQuery) use ($range): void {
                        $orQuery
                            ->whereNull('report_end_at')
                            ->whereBetween('created_at', [$range['start'], $range['end']]);
                    });
            })
            ->latest('report_end_at')
            ->latest()
            ->limit(8)
            ->get();

        $recordRows = DmarcRecord::query()
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $user->id)
            ->when(
                $selectedDomain !== '',
                fn ($query) => $query->where(function ($domainQuery) use ($selectedDomain): void {
                    $domainQuery
                        ->where('dmarc_records.header_from', $selectedDomain)
                        ->orWhere(function ($fallbackQuery) use ($selectedDomain): void {
                            $fallbackQuery
                                ->where(function ($emptyHeader): void {
                                    $emptyHeader
                                        ->whereNull('dmarc_records.header_from')
                                        ->orWhere('dmarc_records.header_from', '');
                                })
                                ->where('dmarc_reports.policy_domain', $selectedDomain);
                        });
                })
            )
            ->whereRaw('COALESCE(dmarc_reports.report_end_at, dmarc_reports.created_at) BETWEEN ? AND ?', [$range['start'], $range['end']])
            ->select([
                'dmarc_records.*',
                'dmarc_reports.id as linked_report_id',
                'dmarc_reports.org_name',
                'dmarc_reports.policy_domain',
                'dmarc_reports.report_end_at',
                'dmarc_reports.created_at as report_created_at',
                'imap_accounts.name as account_name',
            ])
            ->orderByDesc(DB::raw('COALESCE(dmarc_reports.report_end_at, dmarc_reports.created_at)'))
            ->get();

        $topSources = $recordRows
            ->groupBy('source_ip')
            ->map(fn (Collection $rows, string $sourceIp) => (object) [
                'source_ip' => $sourceIp,
                'total_messages' => $rows->sum('message_count'),
            ])
            ->sortByDesc('total_messages')
            ->take(5)
            ->values();

        $domainVolumes = $recordRows
            ->groupBy(fn ($row) => $this->domainForRow($row))
            ->map(fn (Collection $rows, string $domain) => (object) [
                'domain' => $domain,
                'total_messages' => $rows->sum('message_count'),
            ])
            ->sortByDesc('total_messages')
            ->take(8)
            ->values();

        $resultSummary = (object) [
            'dkim_pass' => $recordRows->filter(fn ($row) => strtolower((string) $row->dkim) === 'pass')->sum('message_count'),
            'dkim_fail' => $recordRows->filter(fn ($row) => strtolower((string) $row->dkim) === 'fail')->sum('message_count'),
            'spf_pass' => $recordRows->filter(fn ($row) => strtolower((string) $row->spf) === 'pass')->sum('message_count'),
            'spf_fail' => $recordRows->filter(fn ($row) => strtolower((string) $row->spf) === 'fail')->sum('message_count'),
            'disposition_none' => $recordRows->filter(fn ($row) => strtolower((string) $row->disposition) === 'none')->sum('message_count'),
            'disposition_quarantine' => $recordRows->filter(fn ($row) => strtolower((string) $row->disposition) === 'quarantine')->sum('message_count'),
            'disposition_reject' => $recordRows->filter(fn ($row) => strtolower((string) $row->disposition) === 'reject')->sum('message_count'),
            'disposition_other' => $recordRows->filter(fn ($row) => ! in_array(strtolower((string) $row->disposition), ['none', 'quarantine', 'reject'], true))->sum('message_count'),
        ];

        $timeSeries = $this->buildTimeSeries($recordRows, $range['start']->copy(), $range['end']->copy());

        $failureSummary = collect([
            'all' => $recordRows->filter(fn ($row) => $this->isFailureRow($row))->sum('message_count'),
            'dkim_fail' => $recordRows->filter(fn ($row) => strtolower((string) $row->dkim) === 'fail')->sum('message_count'),
            'spf_fail' => $recordRows->filter(fn ($row) => strtolower((string) $row->spf) === 'fail')->sum('message_count'),
            'quarantine' => $recordRows->filter(fn ($row) => strtolower((string) $row->disposition) === 'quarantine')->sum('message_count'),
            'reject' => $recordRows->filter(fn ($row) => strtolower((string) $row->disposition) === 'reject')->sum('message_count'),
        ]);

        $focusedFailures = $recordRows
            ->filter(fn ($row) => $this->matchesFocus($row, $focus))
            ->take(25)
            ->map(fn ($row) => (object) [
                'source_ip' => $row->source_ip,
                'domain' => $this->domainForRow($row),
                'org_name' => $row->org_name,
                'account_name' => $row->account_name,
                'message_count' => (int) $row->message_count,
                'dkim' => $row->dkim,
                'spf' => $row->spf,
                'disposition' => $row->disposition,
                'report_id' => $row->linked_report_id,
                'reported_at' => $row->report_end_at ? Carbon::parse($row->report_end_at) : Carbon::parse($row->report_created_at),
            ]);

        return view('dashboard', [
            'accounts' => $accounts,
            'recentReports' => $recentReports,
            'topSources' => $topSources,
            'domainVolumes' => $domainVolumes,
            'resultSummary' => $resultSummary,
            'timeSeries' => $timeSeries,
            'range' => $range,
            'rangeQuery' => $rangeQuery,
            'rangeOptions' => $this->rangeOptions(),
            'focus' => $focus,
            'focusOptions' => $this->focusOptions(),
            'domainOptions' => $domainOptions,
            'selectedDomain' => $selectedDomain,
            'failureSummary' => $failureSummary,
            'focusedFailures' => $focusedFailures,
            'stats' => [
                'total_accounts' => $accounts->count(),
                'active_accounts' => $accounts->where('is_active', true)->count(),
                'total_reports' => DmarcReport::query()
                    ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
                    ->count(),
                'last_polled_at' => $this->latestPoll($accounts),
            ],
        ]);
    }

    public function pollNow(Request $request, DmarcIngestionService $ingestionService): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer'],
        ]);

        $accounts = ImapAccount::query()
            ->where('user_id', $request->user()->id)
            ->when(
                filled($validated['account_id'] ?? null),
                fn ($query) => $query->whereKey($validated['account_id']),
                fn ($query) => $query->where('is_active', true)
            )
            ->get();

        abort_if($accounts->isEmpty(), 404);

        $totals = [
            'processed_messages' => 0,
            'imported_reports' => 0,
            'moved_messages' => 0,
            'errors' => 0,
        ];

        foreach ($accounts as $account) {
            $result = $ingestionService->pollAccount($account);

            $totals['processed_messages'] += $result['processed_messages'];
            $totals['imported_reports'] += $result['imported_reports'];
            $totals['moved_messages'] += $result['moved_messages'];
            $totals['errors'] += $result['errors'];
        }

        return to_route('dashboard')->with('status', sprintf(
            'Poll finished: %d messages scanned, %d report(s) imported, %d moved, %d error(s).',
            $totals['processed_messages'],
            $totals['imported_reports'],
            $totals['moved_messages'],
            $totals['errors'],
        ));
    }

    public function showReport(DmarcReport $dmarcReport): View
    {
        abort_unless($dmarcReport->account()->value('user_id') === auth()->id(), 404);

        $dmarcReport->load(['account:id,name,user_id', 'records']);

        return view('dmarc-reports.show', [
            'report' => $dmarcReport,
            'formattedXml' => trim($dmarcReport->raw_xml),
        ]);
    }

    private function latestPoll(Collection $accounts): ?string
    {
        return $accounts
            ->pluck('last_polled_at')
            ->filter()
            ->sortDesc()
            ->first()?->diffForHumans();
    }

    private function rangeOptions(): array
    {
        return [
            '7d' => '7 days',
            '30d' => '30 days',
            '90d' => '90 days',
            '180d' => '6 months',
            '365d' => '12 months',
            'custom' => 'Custom',
        ];
    }

    private function focusOptions(): array
    {
        return [
            'all' => 'All failures',
            'dkim_fail' => 'DKIM fails',
            'spf_fail' => 'SPF fails',
            'quarantine' => 'Quarantine',
            'reject' => 'Reject',
        ];
    }

    private function resolveRange(Request $request): array
    {
        $fromInput = trim((string) $request->input('from', ''));
        $toInput = trim((string) $request->input('to', ''));

        if ($fromInput !== '' || $toInput !== '' || $request->string('range')->toString() === 'custom') {
            $start = $this->parseDateInput($fromInput)?->startOfDay();
            $end = $this->parseDateInput($toInput)?->endOfDay();

            if ($start !== null && $end !== null && $start->lte($end)) {
                return [
                    'value' => 'custom',
                    'label' => 'custom range',
                    'start' => $start,
                    'end' => $end,
                    'days' => $start->diffInDays($end) + 1,
                    'from_input' => $fromInput,
                    'to_input' => $toInput,
                    'is_custom' => true,
                ];
            }
        }

        $range = $request->string('range')->toString();
        $range = array_key_exists($range, $this->rangeOptions()) ? $range : '30d';
        if ($range === 'custom') {
            $range = '30d';
        }
        $days = (int) rtrim($range, 'd');
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        return [
            'value' => $range,
            'label' => $this->rangeOptions()[$range],
            'start' => $start,
            'end' => $end,
            'days' => $days,
            'from_input' => '',
            'to_input' => '',
            'is_custom' => false,
        ];
    }

    /**
     * @return array{range:string,domain?:string,from?:string,to?:string}
     */
    private function rangeQuery(array $range, string $selectedDomain = ''): array
    {
        if (($range['value'] ?? '') === 'custom' && ($range['from_input'] ?? '') !== '' && ($range['to_input'] ?? '') !== '') {
            $query = [
                'range' => 'custom',
                'from' => (string) $range['from_input'],
                'to' => (string) $range['to_input'],
            ];

            if ($selectedDomain !== '') {
                $query['domain'] = $selectedDomain;
            }

            return $query;
        }

        $query = [
            'range' => (string) ($range['value'] ?? '30d'),
        ];

        if ($selectedDomain !== '') {
            $query['domain'] = $selectedDomain;
        }

        return $query;
    }

    private function parseDateInput(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveFocus(string $focus): string
    {
        return array_key_exists($focus, $this->focusOptions()) ? $focus : 'all';
    }

    private function buildTimeSeries(Collection $recordRows, Carbon $start, Carbon $end): Collection
    {
        $buckets = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $buckets->put($key, (object) [
                'key' => $key,
                'label' => $cursor->format('M d'),
                'total_messages' => 0,
                'failed_messages' => 0,
                'passed_messages' => 0,
            ]);

            $cursor->addDay();
        }

        foreach ($recordRows as $row) {
            $timestamp = $row->report_end_at
                ? Carbon::parse($row->report_end_at)
                : Carbon::parse($row->report_created_at);

            $key = $timestamp->format('Y-m-d');

            if (! $buckets->has($key)) {
                continue;
            }

            $bucket = $buckets->get($key);

            if (! is_object($bucket)) {
                continue;
            }

            $bucket->total_messages += (int) $row->message_count;

            if ($this->isFailureRow($row)) {
                $bucket->failed_messages += (int) $row->message_count;
            } else {
                $bucket->passed_messages += (int) $row->message_count;
            }
        }

        return $buckets->values();
    }

    private function isFailureRow(object $row): bool
    {
        return strtolower((string) $row->dkim) === 'fail'
            || strtolower((string) $row->spf) === 'fail'
            || in_array(strtolower((string) $row->disposition), ['quarantine', 'reject'], true);
    }

    private function matchesFocus(object $row, string $focus): bool
    {
        return match ($focus) {
            'dkim_fail' => strtolower((string) $row->dkim) === 'fail',
            'spf_fail' => strtolower((string) $row->spf) === 'fail',
            'quarantine' => strtolower((string) $row->disposition) === 'quarantine',
            'reject' => strtolower((string) $row->disposition) === 'reject',
            default => $this->isFailureRow($row),
        };
    }

    private function domainForRow(object $row): string
    {
        return $row->header_from ?: $row->policy_domain ?: 'Unknown domain';
    }
}
