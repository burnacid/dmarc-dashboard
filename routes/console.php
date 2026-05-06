<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$logScheduleResult = static function (string $scheduleName, string $command): callable {
    return static function (?\Stringable $output = null) use ($scheduleName, $command): void {
        $textOutput = trim((string) $output);

        Log::channel('database')->info('Scheduled command completed successfully.', [
            'schedule_name' => $scheduleName,
            'command' => $command,
            'output' => $textOutput !== '' ? substr($textOutput, 0, 2000) : null,
        ]);
    };
};

$logScheduleFailure = static function (string $scheduleName, string $command): callable {
    return static function (?\Stringable $output = null) use ($scheduleName, $command): void {
        $textOutput = trim((string) $output);

        Log::channel('database')->error('Scheduled command failed.', [
            'schedule_name' => $scheduleName,
            'command' => $command,
            'output' => $textOutput !== '' ? substr($textOutput, 0, 2000) : null,
        ]);
    };
};

Schedule::command('dmarc:poll')
    ->name('schedule.dmarc.poll')
    ->everyFifteenMinutes()
    ->onSuccess($logScheduleResult('schedule.dmarc.poll', 'dmarc:poll'))
    ->onFailure($logScheduleFailure('schedule.dmarc.poll', 'dmarc:poll'))
    ->withoutOverlapping();

Schedule::command('dmarc:check-alerts')
    ->name('schedule.dmarc.check-alerts')
    ->everyFifteenMinutes()
    ->onSuccess($logScheduleResult('schedule.dmarc.check-alerts', 'dmarc:check-alerts'))
    ->onFailure($logScheduleFailure('schedule.dmarc.check-alerts', 'dmarc:check-alerts'))
    ->withoutOverlapping();

Schedule::command('dmarc:collect-dns-records')
    ->name('schedule.dmarc.collect-dns-records')
    ->dailyAt('03:00')
    ->onSuccess($logScheduleResult('schedule.dmarc.collect-dns-records', 'dmarc:collect-dns-records'))
    ->onFailure($logScheduleFailure('schedule.dmarc.collect-dns-records', 'dmarc:collect-dns-records'))
    ->withoutOverlapping();

Schedule::command('dmarc:prune-reports')
    ->name('schedule.dmarc.prune-reports')
    ->dailyAt('02:15')
    ->onSuccess($logScheduleResult('schedule.dmarc.prune-reports', 'dmarc:prune-reports'))
    ->onFailure($logScheduleFailure('schedule.dmarc.prune-reports', 'dmarc:prune-reports'))
    ->withoutOverlapping();

Schedule::command('logs:prune')
    ->name('schedule.logs.prune')
    ->dailyAt('02:45')
    ->onSuccess($logScheduleResult('schedule.logs.prune', 'logs:prune'))
    ->onFailure($logScheduleFailure('schedule.logs.prune', 'logs:prune'))
    ->withoutOverlapping();

