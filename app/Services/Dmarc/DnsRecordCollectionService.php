<?php

namespace App\Services\Dmarc;

use App\Models\DmarcDnsRecordSnapshot;
use App\Models\DmarcRecord;
use App\Models\User;
use App\Services\Dns\TxtRecordResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DnsRecordCollectionService
{
    public function __construct(private readonly TxtRecordResolver $dnsResolver)
    {
    }

    /**
     * @return array{spf:int,dmarc:int,dkim:int,errors:int}
     */
    public function collectForUser(User $user, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $stats = [
            'spf' => 0,
            'dmarc' => 0,
            'dkim' => 0,
            'errors' => 0,
        ];

        foreach ($this->candidateDomains($user) as $domain) {
            $this->snapshot($user->id, 'spf', $domain, $domain, null, $now, $stats);
            $this->snapshot($user->id, 'dmarc', $domain, '_dmarc.'.$domain, null, $now, $stats);
        }

        foreach ($this->candidateDkimHosts($user) as $entry) {
            $this->snapshot(
                $user->id,
                'dkim',
                $entry['domain'],
                $entry['selector'].'._domainkey.'.$entry['domain'],
                $entry['selector'],
                $now,
                $stats,
            );
        }

        return $stats;
    }

    /**
     * @return Collection<int, string>
     */
    private function candidateDomains(User $user): Collection
    {
        $headerFromDomains = DmarcRecord::query()
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $user->id)
            ->whereNotNull('dmarc_records.header_from')
            ->where('dmarc_records.header_from', '!=', '')
            ->distinct()
            ->pluck('dmarc_records.header_from');

        $policyDomains = DmarcRecord::query()
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $user->id)
            ->whereNotNull('dmarc_reports.policy_domain')
            ->where('dmarc_reports.policy_domain', '!=', '')
            ->distinct()
            ->pluck('dmarc_reports.policy_domain');

        return $headerFromDomains
            ->merge($policyDomains)
            ->map(fn ($domain) => $this->normalizeDomain((string) $domain))
            ->filter(fn (string $domain): bool => $domain !== '')
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, array{domain:string,selector:string}>
     */
    private function candidateDkimHosts(User $user): Collection
    {
        return DmarcRecord::query()
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $user->id)
            ->whereNotNull('dmarc_records.dkim_domain')
            ->where('dmarc_records.dkim_domain', '!=', '')
            ->whereNotNull('dmarc_records.dkim_selector')
            ->where('dmarc_records.dkim_selector', '!=', '')
            ->select(['dmarc_records.dkim_domain', 'dmarc_records.dkim_selector'])
            ->distinct()
            ->get()
            ->map(fn ($row): array => [
                'domain' => $this->normalizeDomain((string) $row->dkim_domain),
                'selector' => trim(strtolower((string) $row->dkim_selector)),
            ])
            ->filter(fn (array $row): bool => $row['domain'] !== '' && $row['selector'] !== '')
            ->values();
    }

    /**
     * @param  array{spf:int,dmarc:int,dkim:int,errors:int}  $stats
     */
    private function snapshot(int $userId, string $type, string $domain, string $host, ?string $selector, CarbonInterface $now, array &$stats): void
    {
        try {
            $resolved = $this->dnsResolver->resolveTxtRecords($host);
            $filtered = $this->filterByType($type, $resolved);

            DmarcDnsRecordSnapshot::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'record_type' => $type,
                    'host' => $host,
                    'selector' => $selector,
                ],
                [
                    'domain' => $domain,
                    'records' => $filtered,
                    'status' => $filtered === [] ? 'not_found' : 'found',
                    'error' => null,
                    'fetched_at' => $now,
                ]
            );
        } catch (\Throwable $exception) {
            $stats['errors']++;

            DmarcDnsRecordSnapshot::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'record_type' => $type,
                    'host' => $host,
                    'selector' => $selector,
                ],
                [
                    'domain' => $domain,
                    'records' => [],
                    'status' => 'error',
                    'error' => $exception->getMessage(),
                    'fetched_at' => $now,
                ]
            );
        }

        $stats[$type]++;
    }

    /**
     * @param  list<string>  $records
     * @return list<string>
     */
    private function filterByType(string $type, array $records): array
    {
        $needle = match ($type) {
            'spf' => 'v=spf1',
            'dmarc' => 'v=dmarc1',
            'dkim' => 'v=dkim1',
            default => '',
        };

        if ($needle === '') {
            return [];
        }

        return collect($records)
            ->filter(fn (string $record): bool => str_contains(strtolower($record), $needle))
            ->values()
            ->all();
    }

    private function normalizeDomain(string $domain): string
    {
        return trim(strtolower($domain), " \t\n\r\0\x0B.");
    }
}

