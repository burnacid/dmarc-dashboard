<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Dmarc\DnsRecordCollectionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dmarc:collect-dns-records {--user= : Collect records for one user id}')]
#[Description('Collect SPF, DMARC, and DKIM DNS TXT records based on ingested DMARC domains')]
class DmarcCollectDnsRecordsCommand extends Command
{
    public function handle(DnsRecordCollectionService $service): int
    {
        $users = User::query()
            ->when(
                filled($this->option('user')),
                fn ($query) => $query->whereKey((int) $this->option('user')),
            )
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No users matched the DNS collection criteria.');

            return self::SUCCESS;
        }

        $totals = [
            'spf' => 0,
            'dmarc' => 0,
            'dkim' => 0,
            'errors' => 0,
        ];

        foreach ($users as $user) {
            $stats = $service->collectForUser($user);

            $totals['spf'] += $stats['spf'];
            $totals['dmarc'] += $stats['dmarc'];
            $totals['dkim'] += $stats['dkim'];
            $totals['errors'] += $stats['errors'];

            $this->line(sprintf(
                '[user:%d] %d SPF, %d DMARC, %d DKIM lookups (%d errors)',
                $user->id,
                $stats['spf'],
                $stats['dmarc'],
                $stats['dkim'],
                $stats['errors'],
            ));
        }

        $this->info(sprintf(
            'DNS collection complete. %d SPF, %d DMARC, %d DKIM lookups with %d error(s).',
            $totals['spf'],
            $totals['dmarc'],
            $totals['dkim'],
            $totals['errors'],
        ));

        return self::SUCCESS;
    }
}

