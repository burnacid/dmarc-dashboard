<?php

namespace App\Notifications;

use App\Models\DmarcAlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpfFailRateSpikeNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly DmarcAlertRule $rule,
        private readonly array $payload,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $domain = $this->rule->domain ?: 'All domains';

        return (new MailMessage)
            ->subject('DMARC alert: SPF failure spike detected')
            ->greeting('DMARC alert')
            ->line("Domain scope: {$domain}")
            ->line(sprintf(
                'Current SPF fail rate: %.2f%% (%d/%d messages)',
                (float) $this->payload['current_fail_rate'],
                (int) $this->payload['current_spf_fail_messages'],
                (int) $this->payload['current_total_messages'],
            ))
            ->line(sprintf(
                'Baseline SPF fail rate: %.2f%% (%d/%d messages)',
                (float) $this->payload['baseline_fail_rate'],
                (int) $this->payload['baseline_spf_fail_messages'],
                (int) $this->payload['baseline_total_messages'],
            ))
            ->line(sprintf(
                'Absolute increase: %.2f percentage points',
                (float) $this->payload['absolute_increase'],
            ))
            ->line('Review your DMARC report dashboard for affected sources and domains.');
    }
}

