<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\ImapAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DmarcReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = [
            'account_id' => $request->integer('account_id') ?: null,
            'domain' => trim((string) $request->input('domain', '')),
            'org' => trim((string) $request->input('org', '')),
            'report_id' => trim((string) $request->input('report_id', '')),
            'per_page' => in_array($request->integer('per_page'), [10, 25, 50, 100], true)
                ? $request->integer('per_page')
                : 25,
        ];

        $accounts = ImapAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $reports = DmarcReport::query()
            ->with('account:id,name,user_id')
            ->with('records:id,dmarc_report_id,message_count,dkim,spf')
            ->withCount('records')
            ->withSum('records as total_messages', 'message_count')
            ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['account_id'], fn ($query, $accountId) => $query->where('imap_account_id', $accountId))
            ->when($filters['domain'] !== '', fn ($query) => $query->where('policy_domain', 'like', '%'.$filters['domain'].'%'))
            ->when($filters['org'] !== '', fn ($query) => $query->where('org_name', 'like', '%'.$filters['org'].'%'))
            ->when($filters['report_id'] !== '', fn ($query) => $query->where('external_report_id', 'like', '%'.$filters['report_id'].'%'))
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
}

