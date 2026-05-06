<?php

namespace App\Notifications;

use App\Models\DmarcAlertRule;
use App\Notifications\Channels\NtfyChannel;
use App\Notifications\Channels\NtfyMessage;
use App\Support\Notifications\DmarcAlertChannelTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DkimFailRateSpikeNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly DmarcAlertRule $rule,
        private readonly array $payload,
        public readonly ?DmarcAlertChannelTarget $target = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($this->target !== null) {
            return $this->target->isNtfy() ? [NtfyChannel::class] : ['mail'];
        }

        $channels = ['mail'];

        if (filled($this->rule->ntfy_url)) {
            $channels[] = NtfyChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $domain = $this->rule->domain ?: 'All domains';

        return (new MailMessage)
            ->subject('DMARC alert: DKIM failure spike detected')
            ->greeting('DMARC alert')
            ->line("Domain scope: {$domain}")
            ->line(sprintf(
                'Current DKIM fail rate: %.2f%% (%d/%d messages)',
                (float) $this->payload['current_fail_rate'],
                (int) $this->payload['current_dkim_fail_messages'],
                (int) $this->payload['current_total_messages'],
            ))
            ->line(sprintf(
                'Baseline DKIM fail rate: %.2f%% (%d/%d messages)',
                (float) $this->payload['baseline_fail_rate'],
                (int) $this->payload['baseline_dkim_fail_messages'],
                (int) $this->payload['baseline_total_messages'],
            ))
            ->line(sprintf(
                'Absolute increase: %.2f percentage points',
                (float) $this->payload['absolute_increase'],
            ))
            ->line('Review your DMARC report dashboard for affected sources and domains.');
    }

    public function toNtfy(object $notifiable): NtfyMessage
    {
        $domain = $this->rule->domain ?: 'All domains';

        $message = NtfyMessage::create(sprintf(
            "**Domain:** `%s`\n\n**Current DKIM fail rate:** `%.2f%%` (%d/%d)\n**Baseline DKIM fail rate:** `%.2f%%` (%d/%d)\n**Increase:** `%.2f pp`\n\nReview the DMARC dashboard for details.",
            $domain,
            (float) $this->payload['current_fail_rate'],
            (int) $this->payload['current_dkim_fail_messages'],
            (int) $this->payload['current_total_messages'],
            (float) $this->payload['baseline_fail_rate'],
            (int) $this->payload['baseline_dkim_fail_messages'],
            (int) $this->payload['baseline_total_messages'],
            (float) $this->payload['absolute_increase'],
        ))
            ->title('DMARC alert: DKIM failure spike detected')
            ->priority(4)
            ->tags(['warning', 'dmarc', 'dkim']);

        $alertsUrl = rtrim((string) config('app.url'), '/');

        if ($alertsUrl !== '') {
            $message->clickUrl($alertsUrl.'/alerts');
        }

        return $message;
    }
}

