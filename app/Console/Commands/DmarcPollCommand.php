<?php

namespace App\Console\Commands;

use App\Models\ImapAccount;
use App\Services\Dmarc\DmarcIngestionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dmarc:poll {--account= : Poll a single IMAP account by id}')]
#[Description('Poll configured IMAP accounts and import DMARC aggregate reports')]
class DmarcPollCommand extends Command
{
    public function handle(DmarcIngestionService $ingestionService): int
    {
        $accounts = ImapAccount::query()
            ->when(
                filled($this->option('account')),
                fn ($query) => $query->whereKey((int) $this->option('account')),
                fn ($query) => $query->where('is_active', true)
            )
            ->get();

        if ($accounts->isEmpty()) {
            $this->warn('No IMAP accounts matched the poll criteria.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Polling %d IMAP account(s)...', $accounts->count()));

        foreach ($accounts as $account) {
            $stats = $ingestionService->pollAccount($account);

            $this->line(sprintf(
                '[%s] %d messages scanned, %d report(s) imported, %d moved, %d error(s)',
                $account->name,
                $stats['processed_messages'],
                $stats['imported_reports'],
                $stats['moved_messages'],
                $stats['errors'],
            ));
        }

        $this->info('DMARC polling completed.');

        return self::SUCCESS;
    }
}
