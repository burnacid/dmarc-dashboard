<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DomainFilterController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
        ]);

        $domain = trim((string) $request->input('domain', ''));

        if ($domain !== '' && ! $this->availableDomains($request)->contains($domain)) {
            $domain = '';
        }

        $request->session()->put('filters.domain', $domain);

        return back();
    }

    private function availableDomains(Request $request)
    {
        return DB::table('dmarc_records')
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $request->user()->id)
            ->selectRaw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain) as domain")
            ->whereNotNull(DB::raw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain)"))
            ->distinct()
            ->pluck('domain');
    }
}

