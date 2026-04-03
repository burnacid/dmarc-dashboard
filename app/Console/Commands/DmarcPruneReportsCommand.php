<?php

namespace App\Console\Commands;

use App\Models\DmarcReport;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dmarc:prune-reports')]
#[Description('Delete DMARC reports older than each user configured retention period')]
class DmarcPruneReportsCommand extends Command
{
    public function handle(): int
    {
        $totalDeleted = 0;

        User::query()
            ->whereNotNull('report_retention_days')
            ->where('report_retention_days', '>', 0)
            ->chunkById(100, function ($users) use (&$totalDeleted): void {
                foreach ($users as $user) {
                    $cutoff = now()->subDays((int) $user->report_retention_days);

                    $deleted = DmarcReport::query()
                        ->whereHas('account', fn ($query) => $query->where('user_id', $user->id))
                        ->whereRaw('COALESCE(report_end_at, created_at) < ?', [$cutoff])
                        ->delete();

                    $totalDeleted += $deleted;
                }
            });

        $this->info(sprintf('DMARC retention cleanup complete. %d report(s) removed.', $totalDeleted));

        return self::SUCCESS;
    }
}

