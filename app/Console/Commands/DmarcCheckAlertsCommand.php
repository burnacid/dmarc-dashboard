<?php

namespace App\Console\Commands;

use App\Models\DmarcAlertRule;
use App\Services\Dmarc\DmarcAlertNotificationDispatcher;
use App\Services\Dmarc\DkimFailRateSpikeAlertService;
use App\Services\Dmarc\SpfFailRateSpikeAlertService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('dmarc:check-alerts')]
#[Description('Evaluate DMARC alert rules and notify users when thresholds are crossed')]
class DmarcCheckAlertsCommand extends Command
{
    public function handle(
        SpfFailRateSpikeAlertService $spfService,
        DkimFailRateSpikeAlertService $dkimService,
        DmarcAlertNotificationDispatcher $dispatcher,
    ): int {
        $now = Carbon::now();

        $rules = DmarcAlertRule::query()
            ->with(['user', 'notificationChannels'])
            ->where('is_active', true)
            ->whereIn('metric', ['spf_fail_rate_spike', 'dkim_fail_rate_spike'])
            ->get();

        if ($rules->isEmpty()) {
            $this->info('No active DMARC alert rules found.');

            return self::SUCCESS;
        }

        $triggeredCount = 0;

        foreach ($rules as $rule) {
            if ($this->isInCooldown($rule, $now)) {
                continue;
            }

            $payload = match ($rule->metric) {
                'dkim_fail_rate_spike' => $dkimService->evaluate($rule, $now),
                default                => $spfService->evaluate($rule, $now),
            };

            if ($payload === null) {
                continue;
            }

            // Persist event — DKIM fail messages stored in context for forward-compat
            $eventData = [
                'triggered_at'             => $now,
                'current_total_messages'   => $payload['current_total_messages'],
                'current_fail_rate'        => $payload['current_fail_rate'],
                'baseline_total_messages'  => $payload['baseline_total_messages'],
                'baseline_fail_rate'       => $payload['baseline_fail_rate'],
                'context' => [
                    'window_start'     => $payload['window_start']->toIso8601String(),
                    'window_end'       => $payload['window_end']->toIso8601String(),
                    'baseline_start'   => $payload['baseline_start']->toIso8601String(),
                    'baseline_end'     => $payload['baseline_end']->toIso8601String(),
                    'absolute_increase' => $payload['absolute_increase'],
                ],
            ];

            if ($rule->metric === 'dkim_fail_rate_spike') {
                $eventData['current_spf_fail_messages']  = 0;
                $eventData['baseline_spf_fail_messages'] = 0;
                $eventData['context']['current_dkim_fail_messages']  = $payload['current_dkim_fail_messages'];
                $eventData['context']['baseline_dkim_fail_messages'] = $payload['baseline_dkim_fail_messages'];
            } else {
                $eventData['current_spf_fail_messages']  = $payload['current_spf_fail_messages'];
                $eventData['baseline_spf_fail_messages'] = $payload['baseline_spf_fail_messages'];
            }

            $rule->events()->create($eventData);

            $dispatcher->dispatch($rule, $payload);

            Log::channel('system')->info('dmarc.alert.triggered', [
                'rule_id' => $rule->id,
                'metric'  => $rule->metric,
                'domain'  => $rule->domain,
                'channel_count' => $rule->notificationChannels->count(),
            ]);

            $triggeredCount++;
        }

        $this->info(sprintf('DMARC alert evaluation complete. %d alert(s) triggered.', $triggeredCount));

        return self::SUCCESS;
    }

    private function isInCooldown(DmarcAlertRule $rule, Carbon $now): bool
    {
        $cooldownCutoff = $now->copy()->subMinutes((int) $rule->cooldown_minutes);

        return $rule->events()
            ->where('triggered_at', '>=', $cooldownCutoff)
            ->exists();
    }
}

