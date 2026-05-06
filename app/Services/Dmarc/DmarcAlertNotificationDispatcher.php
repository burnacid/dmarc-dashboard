<?php

namespace App\Services\Dmarc;

use App\Models\DmarcAlertRule;
use App\Notifications\DkimFailRateSpikeNotification;
use App\Notifications\SpfFailRateSpikeNotification;
use App\Support\Notifications\DmarcAlertChannelTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class DmarcAlertNotificationDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(DmarcAlertRule $rule, array $payload): void
    {
        $targets = $this->buildTargets($rule);

        foreach ($targets as $target) {
            $notification = $this->makeNotification($rule, $payload, $target);

            if ($target->isMail()) {
                $recipient = (string) ($target->config['email_to'] ?? $rule->user->email);

                Notification::route('mail', $recipient)->notify($notification);

                continue;
            }

            // NtfyChannel reads ntfy destination data from the notification target.
            Notification::route('mail', $rule->user->email)->notify($notification);
        }
    }

    /**
     * @return Collection<int, DmarcAlertChannelTarget>
     */
    private function buildTargets(DmarcAlertRule $rule): Collection
    {
        $channels = $rule->relationLoaded('notificationChannels')
            ? $rule->notificationChannels->where('is_active', true)->values()
            : $rule->notificationChannels()->where('is_active', true)->get();

        if ($channels->isNotEmpty()) {
            return $channels->map(function ($channel): DmarcAlertChannelTarget {
                return new DmarcAlertChannelTarget($channel->type, [
                    'email_to' => $channel->email_to,
                    'ntfy_url' => $channel->ntfy_url,
                    'ntfy_token' => $channel->ntfy_token,
                    'ntfy_ignore_certificate' => $channel->ntfy_ignore_certificate,
                ]);
            })->values();
        }

        // Backward compatibility fallback while legacy rule columns still exist.
        $targets = collect();

        $targets->push(new DmarcAlertChannelTarget('email', [
            'email_to' => filled($rule->notification_email) ? $rule->notification_email : $rule->user->email,
        ]));

        if (filled($rule->ntfy_url)) {
            $targets->push(new DmarcAlertChannelTarget('ntfy', [
                'ntfy_url' => $rule->ntfy_url,
                'ntfy_token' => $rule->ntfy_token,
                'ntfy_ignore_certificate' => (bool) $rule->ntfy_ignore_certificate,
            ]));
        }

        return $targets;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function makeNotification(DmarcAlertRule $rule, array $payload, DmarcAlertChannelTarget $target): SpfFailRateSpikeNotification|DkimFailRateSpikeNotification
    {
        return $rule->metric === 'dkim_fail_rate_spike'
            ? new DkimFailRateSpikeNotification($rule, $payload, $target)
            : new SpfFailRateSpikeNotification($rule, $payload, $target);
    }
}

