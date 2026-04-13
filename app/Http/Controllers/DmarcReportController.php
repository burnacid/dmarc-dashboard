<?php

namespace App\Http\Controllers;

use App\Models\DmarcDnsRecordSnapshot;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DmarcReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $rangeOptions = $this->rangeOptionsForUser($user);
        $range = $this->resolveRange(
            $request->string('range')->toString(),
            trim((string) $request->input('from', '')),
            trim((string) $request->input('to', '')),
            $rangeOptions,
        );

        $filters = [
            'account_id' => $request->integer('account_id') ?: null,
            'domain' => trim((string) $request->input('domain', (string) $request->session()->get('filters.domain', ''))),
            'org' => trim((string) $request->input('org', '')),
            'report_id' => trim((string) $request->input('report_id', '')),
            'range' => $range['value'],
            'from' => $range['from_input'],
            'to' => $range['to_input'],
            'per_page' => in_array($request->integer('per_page'), [10, 25, 50, 100], true)
                ? $request->integer('per_page')
                : 25,
        ];

        $accounts = ImapAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $domains = DmarcReport::query()
            ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
            ->whereNotNull('policy_domain')
            ->where('policy_domain', '!=', '')
            ->distinct()
            ->orderBy('policy_domain')
            ->pluck('policy_domain');

        if ($filters['domain'] !== '' && ! $domains->contains($filters['domain'])) {
            $filters['domain'] = '';
        }

        $drillDown = $this->buildDrillDownContext($filters, $range);

        $request->session()->put('filters.domain', $filters['domain']);

        $reports = DmarcReport::query()
            ->with('account:id,name,user_id')
            ->with('records:id,dmarc_report_id,message_count,dkim,spf')
            ->withCount('records')
            ->withSum('records as total_messages', 'message_count')
            ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['account_id'], fn ($query, $accountId) => $query->where('imap_account_id', $accountId))
            ->when($filters['domain'] !== '', fn ($query) => $query->where('policy_domain', $filters['domain']))
            ->when($filters['org'] !== '', fn ($query) => $query->where('org_name', 'like', '%'.$filters['org'].'%'))
            ->when($filters['report_id'] !== '', fn ($query) => $query->where('external_report_id', 'like', '%'.$filters['report_id'].'%'))
            ->when(
                $range['start'] !== null && $range['end'] !== null,
                fn ($query) => $query->whereRaw('COALESCE(report_end_at, created_at) BETWEEN ? AND ?', [$range['start'], $range['end']])
            )
            ->orderByRaw('COALESCE(report_end_at, created_at) desc')
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $reports->getCollection()->transform(function (DmarcReport $report): DmarcReport {
            $report->dkim_pass_messages = $report->records
                ->filter(fn ($record) => strtolower((string) $record->dkim) === 'pass')
                ->sum('message_count');
            $report->dkim_fail_messages = $report->records
                ->filter(fn ($record) => strtolower((string) $record->dkim) === 'fail')
                ->sum('message_count');
            $report->spf_pass_messages = $report->records
                ->filter(fn ($record) => strtolower((string) $record->spf) === 'pass')
                ->sum('message_count');
            $report->spf_fail_messages = $report->records
                ->filter(fn ($record) => strtolower((string) $record->spf) === 'fail')
                ->sum('message_count');

            return $report;
        });

        return view('dmarc-reports.index', [
            'reports' => $reports,
            'accounts' => $accounts,
            'filters' => $filters,
            'rangeOptions' => $rangeOptions,
            'drillDown' => $drillDown,
        ]);
    }

    public function show(DmarcReport $dmarcReport): View
    {
        abort_unless($dmarcReport->account()->value('user_id') === auth()->id(), 404);

        $dmarcReport->load(['account:id,name,user_id', 'records']);

        return view('dmarc-reports.show', [
            'report' => $dmarcReport,
            'formattedXml' => $this->formatXml($dmarcReport->raw_xml),
            'dnsSnapshotContext' => $this->buildDnsSnapshotContext($dmarcReport, (int) auth()->id()),
        ]);
    }

    private function formatXml(string $xml): string
    {

        return trim($xml);
    }

    /**
     * @return array<string, string>
     */
    private function rangeOptionsForUser(User $user): array
    {
        $allowed = User::allowedRangePresets();
        $selected = $user->normalizedRangePresets();
        $presetOptions = collect($selected)
            ->filter(fn (string $preset) => array_key_exists($preset, $allowed))
            ->mapWithKeys(fn (string $preset) => [$preset => $allowed[$preset]])
            ->all();

        return [
            'all' => 'All time',
            ...$presetOptions,
            'custom' => 'Custom',
        ];
    }

    /**
     * @return array{value:string,start:Carbon|null,end:Carbon|null,from_input:string,to_input:string}
     */
    private function resolveRange(string $range, string $fromInput, string $toInput, array $rangeOptions): array
    {
        if ($fromInput !== '' || $toInput !== '' || $range === 'custom') {
            $start = $this->parseDateInput($fromInput)?->startOfDay();
            $end = $this->parseDateInput($toInput)?->endOfDay();

            if ($start !== null && $end !== null && $start->lte($end)) {
                return [
                    'value' => 'custom',
                    'start' => $start,
                    'end' => $end,
                    'from_input' => $fromInput,
                    'to_input' => $toInput,
                ];
            }
        }

        if ($range === 'all' || $range === '') {
            return [
                'value' => 'all',
                'start' => null,
                'end' => null,
                'from_input' => '',
                'to_input' => '',
            ];
        }

        if (array_key_exists($range, $rangeOptions) && str_ends_with($range, 'd')) {
            $days = (int) rtrim($range, 'd');

            return [
                'value' => $range,
                'start' => now()->subDays($days - 1)->startOfDay(),
                'end' => now()->endOfDay(),
                'from_input' => '',
                'to_input' => '',
            ];
        }

        return [
            'value' => 'all',
            'start' => null,
            'end' => null,
            'from_input' => '',
            'to_input' => '',
        ];
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

    /**
     * @param  array{account_id:int|null,domain:string,org:string,report_id:string,range:string,from:string,to:string,per_page:int}  $filters
     * @param  array{value:string,start:Carbon|null,end:Carbon|null,from_input:string,to_input:string}  $range
     * @return array{is_day_drilldown:bool,date:string|null,date_label:string|null,clear_url:string|null,previous_day_url:string|null,next_day_url:string|null}
     */
    private function buildDrillDownContext(array $filters, array $range): array
    {
        $isDayDrilldown = $range['value'] === 'custom'
            && $range['from_input'] !== ''
            && $range['from_input'] === $range['to_input'];

        if (! $isDayDrilldown || $range['start'] === null) {
            return [
                'is_day_drilldown' => false,
                'date' => null,
                'date_label' => null,
                'clear_url' => null,
                'previous_day_url' => null,
                'next_day_url' => null,
            ];
        }

        $baseFilters = array_filter([
            'account_id' => $filters['account_id'],
            'domain' => $filters['domain'],
            'org' => $filters['org'],
            'report_id' => $filters['report_id'],
            'per_page' => $filters['per_page'],
        ], fn ($value) => $value !== null && $value !== '');

        $date = $range['from_input'];
        $current = $range['start']->copy()->startOfDay();

        return [
            'is_day_drilldown' => true,
            'date' => $date,
            'date_label' => $current->format('M d, Y'),
            'clear_url' => route('reports.index', array_merge($baseFilters, ['range' => 'all'])),
            'previous_day_url' => route('reports.index', array_merge($baseFilters, [
                'range' => 'custom',
                'from' => $current->copy()->subDay()->format('Y-m-d'),
                'to' => $current->copy()->subDay()->format('Y-m-d'),
            ])),
            'next_day_url' => route('reports.index', array_merge($baseFilters, [
                'range' => 'custom',
                'from' => $current->copy()->addDay()->format('Y-m-d'),
                'to' => $current->copy()->addDay()->format('Y-m-d'),
            ])),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildDnsSnapshotContext(DmarcReport $report, int $userId): array
    {
        $domains = $report->records
            ->map(fn ($record) => trim(strtolower((string) ($record->header_from ?: $report->policy_domain))))
            ->filter(fn (string $domain) => $domain !== '')
            ->unique()
            ->values();

        $dkimTargets = $report->records
            ->map(function ($record): ?array {
                $domain = trim(strtolower((string) $record->dkim_domain));
                $selector = trim(strtolower((string) $record->dkim_selector));

                if ($domain === '' || $selector === '') {
                    return null;
                }

                return [
                    'domain' => $domain,
                    'selector' => $selector,
                    'host' => $selector.'._domainkey.'.$domain,
                ];
            })
            ->filter()
            ->unique(fn (array $target) => $target['host'])
            ->values();

        $hosts = $domains
            ->merge($domains->map(fn (string $domain) => '_dmarc.'.$domain))
            ->merge($dkimTargets->pluck('host'))
            ->unique()
            ->values();

        $snapshots = DmarcDnsRecordSnapshot::query()
            ->where('user_id', $userId)
            ->whereIn('host', $hosts)
            ->get()
            ->keyBy(fn (DmarcDnsRecordSnapshot $snapshot) => $snapshot->record_type.'|'.$snapshot->host);

        $spf = $domains
            ->map(fn (string $domain): array => $this->snapshotRow($this->snapshotFromCollection($snapshots, 'spf|'.$domain), $domain, $domain))
            ->all();

        $dmarc = $domains
            ->map(fn (string $domain): array => $this->snapshotRow($this->snapshotFromCollection($snapshots, 'dmarc|'.'_dmarc.'.$domain), $domain, '_dmarc.'.$domain))
            ->all();

        $dkim = $dkimTargets
            ->map(fn (array $target): array => $this->snapshotRow(
                $this->snapshotFromCollection($snapshots, 'dkim|'.$target['host']),
                $target['domain'],
                $target['host'],
                $target['selector']
            ))
            ->all();

        return [
            'spf' => $spf,
            'dmarc' => $dmarc,
            'dkim' => $dkim,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotRow(?DmarcDnsRecordSnapshot $snapshot, string $domain, string $host, ?string $selector = null): array
    {
        return [
            'domain' => $domain,
            'host' => $host,
            'selector' => $selector,
            'status' => $snapshot?->status ?? 'not_collected',
            'records' => array_values(array_map('strval', (array) ($snapshot?->records ?? []))),
            'fetched_at' => $snapshot?->fetched_at,
        ];
    }

    private function snapshotFromCollection(Collection $snapshots, string $key): ?DmarcDnsRecordSnapshot
    {
        $snapshot = $snapshots->get($key);

        return $snapshot instanceof DmarcDnsRecordSnapshot ? $snapshot : null;
    }
}
