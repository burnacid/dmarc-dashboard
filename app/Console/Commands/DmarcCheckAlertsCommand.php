<?php

namespace App\Console\Commands;

use App\Models\DmarcAlertRule;
use App\Notifications\SpfFailRateSpikeNotification;
use App\Services\Dmarc\SpfFailRateSpikeAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[Signature('dmarc:check-alerts')]
#[Description('Evaluate DMARC alert rules and notify users when thresholds are crossed')]
class DmarcCheckAlertsCommand extends Command
{
    public function handle(SpfFailRateSpikeAlertService $alertService): int
    {
        $now = now();

        $rules = DmarcAlertRule::query()
            ->with('user')
            ->where('is_active', true)
            ->where('metric', 'spf_fail_rate_spike')
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

            $payload = $alertService->evaluate($rule, $now);

            if ($payload === null) {
                continue;
            }

            $rule->events()->create([
                'triggered_at' => $now,
                'current_total_messages' => $payload['current_total_messages'],
                'current_spf_fail_messages' => $payload['current_spf_fail_messages'],
                'current_fail_rate' => $payload['current_fail_rate'],
                'baseline_total_messages' => $payload['baseline_total_messages'],
                'baseline_spf_fail_messages' => $payload['baseline_spf_fail_messages'],
                'baseline_fail_rate' => $payload['baseline_fail_rate'],
                'context' => [
                    'window_start' => $payload['window_start']->toIso8601String(),
                    'window_end' => $payload['window_end']->toIso8601String(),
                    'baseline_start' => $payload['baseline_start']->toIso8601String(),
                    'baseline_end' => $payload['baseline_end']->toIso8601String(),
                    'absolute_increase' => $payload['absolute_increase'],
                ],
            ]);

            $recipient = filled($rule->notification_email) ? $rule->notification_email : $rule->user->email;
            Notification::route('mail', $recipient)
                ->notify(new SpfFailRateSpikeNotification($rule, $payload));

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

