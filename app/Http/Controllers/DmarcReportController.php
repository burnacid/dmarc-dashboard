<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\ImapAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DmarcReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $range = $this->resolveRange(
            $request->string('range')->toString(),
            trim((string) $request->input('from', '')),
            trim((string) $request->input('to', '')),
        );

        $filters = [
            'account_id' => $request->integer('account_id') ?: null,
            'domain' => trim((string) $request->input('domain', '')),
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
            'domains' => $domains,
            'filters' => $filters,
            'rangeOptions' => $this->rangeOptions(),
        ]);
    }

    public function show(DmarcReport $dmarcReport): View
    {
        abort_unless($dmarcReport->account()->value('user_id') === auth()->id(), 404);

        $dmarcReport->load(['account:id,name,user_id', 'records']);

        return view('dmarc-reports.show', [
            'report' => $dmarcReport,
            'formattedXml' => $this->formatXml($dmarcReport->raw_xml),
        ]);
    }

    private function formatXml(string $xml): string
    {

        return trim($xml);
    }

    /**
     * @return array<string, string>
     */
    private function rangeOptions(): array
    {
        return [
            'all' => 'All time',
            '7d' => '7 days',
            '30d' => '30 days',
            '90d' => '90 days',
            '180d' => '6 months',
            '365d' => '12 months',
            'custom' => 'Custom',
        ];
    }

    /**
     * @return array{value:string,start:Carbon|null,end:Carbon|null,from_input:string,to_input:string}
     */
    private function resolveRange(string $range, string $fromInput, string $toInput): array
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

        if (array_key_exists($range, $this->rangeOptions()) && str_ends_with($range, 'd')) {
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
}

