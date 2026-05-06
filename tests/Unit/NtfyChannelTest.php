<?php

namespace Tests\Unit;

use App\Notifications\Channels\NtfyChannel;
use App\Notifications\Channels\NtfyMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NtfyChannelTest extends TestCase
{
    public function test_it_sends_ntfy_payload_to_rule_url(): void
    {
        Http::fake();

        $notification = new class extends Notification
        {
            public object $rule;

            public function __construct()
            {
                $this->rule = (object) [
                    'ntfy_url' => 'https://ntfy.example.test/DMARC',
                    'ntfy_token' => 'secret-token',
                ];
            }

            public function toNtfy(object $notifiable): NtfyMessage
            {
                return NtfyMessage::create('Body text')
                    ->title('DMARC alert')
                    ->priority(4)
                    ->tags(['warning', 'dmarc'])
                    ->clickUrl('https://dashboard.example.test/alerts');
            }
        };

        (new NtfyChannel())->send((object) [], $notification);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://ntfy.example.test/DMARC'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request->hasHeader('Title', 'DMARC alert')
                && $request->hasHeader('Priority', '4')
                && $request->hasHeader('Tags', 'warning,dmarc')
                && $request->hasHeader('Click', 'https://dashboard.example.test/alerts')
                && $request->hasHeader('Markdown', 'yes')
                && $request->body() === 'Body text';
        });
    }

    public function test_it_uses_strict_tls_verification_by_default(): void
    {
        config()->set('services.ntfy.ca_bundle', null);

        $channel = new class extends NtfyChannel
        {
            public function verifyOption(): bool|string
            {
                return $this->resolveVerifyOption();
            }
        };

        $this->assertTrue($channel->verifyOption());
    }

    public function test_it_uses_custom_ca_bundle_when_configured(): void
    {
        config()->set('services.ntfy.ca_bundle', 'C:\\certs\\private-ntfy-ca.pem');

        $channel = new class extends NtfyChannel
        {
            public function verifyOption(): bool|string
            {
                return $this->resolveVerifyOption();
            }
        };

        $this->assertSame('C:\\certs\\private-ntfy-ca.pem', $channel->verifyOption());
    }

    public function test_it_can_disable_tls_verification_for_a_rule_that_ignores_certificates(): void
    {
        config()->set('services.ntfy.ca_bundle', 'C:\\certs\\private-ntfy-ca.pem');

        $channel = new class extends NtfyChannel
        {
            public function verifyOption(mixed $rule = null, bool $ignoreCertificate = false): bool|string
            {
                return $this->resolveVerifyOption($rule, $ignoreCertificate);
            }
        };

        $rule = (object) [
            'ntfy_ignore_certificate' => true,
        ];

        $this->assertFalse($channel->verifyOption($rule));
    }
}

