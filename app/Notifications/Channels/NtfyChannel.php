<?php

namespace App\Notifications\Channels;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NtfyChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toNtfy')) {
            return;
        }

        /** @var NtfyMessage $message */
        $message = $notification->toNtfy($notifiable);

        $url = $message->clickUrl ?: '';

        // The ntfy_url on the rule is the full topic URL: https://ntfy.sh/my-topic
        // We retrieve it from the notification if it exposes a rule property.
        $ntfyUrl = '';
        $ntfyToken = '';
        $ntfyIgnoreCertificate = false;
        $rule = null;

        if (property_exists($notification, 'rule')) {
            $rule = $notification->rule; // @phpstan-ignore-line
            $ntfyUrl = (string) ($rule->ntfy_url ?? '');
            $ntfyToken = (string) ($rule->ntfy_token ?? '');
            $ntfyIgnoreCertificate = (bool) ($rule->ntfy_ignore_certificate ?? false);
        }

        if (property_exists($notification, 'target') && $notification->target !== null) {
            $targetConfig = (array) ($notification->target->config ?? []); // @phpstan-ignore-line
            $ntfyUrl = (string) ($targetConfig['ntfy_url'] ?? $ntfyUrl);
            $ntfyToken = (string) ($targetConfig['ntfy_token'] ?? $ntfyToken);
            $ntfyIgnoreCertificate = (bool) ($targetConfig['ntfy_ignore_certificate'] ?? $ntfyIgnoreCertificate);
        }

        if (blank($ntfyUrl)) {
            return;
        }

        try {
            $this->buildRequest(
                $ntfyToken,
                $rule,
                $ntfyIgnoreCertificate,
                $this->buildHeaders($message, $url)
            )->send('POST', $ntfyUrl, [
                'body' => $message->body,
            ]);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();

            Log::warning('NtfyChannel: failed to send notification', [
                'url'   => $ntfyUrl,
                'error' => $errorMessage,
                'hint' => str_contains($errorMessage, 'cURL error 60')
                    ? 'If this ntfy server uses a private or self-signed CA, set NTFY_CA_BUNDLE to the PEM bundle path trusted by this app.'
                    : null,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function buildRequest(?string $ntfyToken = null, mixed $rule = null, bool $ignoreCertificate = false, array $headers = []): PendingRequest
    {
        $request = Http::withHeaders($headers)
            ->withOptions(['verify' => $this->resolveVerifyOption($rule, $ignoreCertificate)]);

        if (filled($ntfyToken)) {
            $request = $request->withToken($ntfyToken);
        }

        return $request;
    }

    /**
     * @return array<string, string>
     */
    protected function buildHeaders(NtfyMessage $message, string $clickUrl = ''): array
    {
        $headers = [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Markdown' => 'yes',
            'Title' => $message->title !== '' ? $message->title : 'DMARC alert',
            'Priority' => (string) $message->priority,
        ];

        if ($message->tags !== []) {
            $headers['Tags'] = implode(',', $message->tags);
        }

        if ($clickUrl !== '') {
            $headers['Click'] = $clickUrl;
        }

        return $headers;
    }

    protected function resolveVerifyOption(mixed $rule = null, bool $ignoreCertificate = false): bool|string
    {
        if ($ignoreCertificate || (bool) data_get($rule, 'ntfy_ignore_certificate', false)) {
            return false;
        }

        $caBundle = config('services.ntfy.ca_bundle');

        return is_string($caBundle) && trim($caBundle) !== ''
            ? trim($caBundle)
            : true;
    }
}

