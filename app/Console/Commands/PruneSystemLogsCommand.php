<?php

namespace App\Console\Commands;

use App\Models\AuthDiagnosticLog;
use App\Models\SystemLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('logs:prune')]
#[Description('Prune system and auth diagnostic logs older than the configured retention period')]
class PruneSystemLogsCommand extends Command
{
    public function handle(): int
    {
        $retentionDays = (int) config('app.system_logs_retention_days', 30);

        if ($retentionDays < 1) {
            $this->error('Log retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($retentionDays);

        $deletedSystemLogs = SystemLog::query()
            ->where('logged_at', '<', $cutoff)
            ->delete();

        $deletedAuthDiagnosticLogs = AuthDiagnosticLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Log prune complete. Removed %d system log(s) and %d auth diagnostic log(s) older than %d day(s).',
            $deletedSystemLogs,
            $deletedAuthDiagnosticLogs,
            $retentionDays,
        ));

        return self::SUCCESS;
    }
}

