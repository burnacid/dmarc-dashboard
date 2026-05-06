<?php

namespace Tests\Feature;

use App\Models\DmarcAlertRule;
use App\Models\DmarcNotificationChannel;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_report_settings_from_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.report-settings.update'), [
                'report_retention_days' => 180,
                'dashboard_range_presets' => ['7d', '30d', '180d'],
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame(180, $user->report_retention_days);
        $this->assertSame(['7d', '30d', '180d'], $user->dashboard_range_presets);
    }

    public function test_user_can_create_and_update_alert_rules_from_alerts_page(): void
    {
        $user = User::factory()->create();

        $channel = DmarcNotificationChannel::query()->create([
            'user_id' => $user->id,
            'name' => 'Ops Email',
            'type' => 'email',
            'email_to' => 'ops@example.com',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('alerts.rules.store'), [
                'name' => 'SPF spike all domains',
                'metric' => 'spf_fail_rate_spike',
                'domain' => null,
                'threshold_multiplier' => 2.5,
                'min_absolute_increase' => 10,
                'min_messages' => 300,
                'window_minutes' => 1440,
                'baseline_days' => 21,
                'cooldown_minutes' => 720,
                'is_active' => 1,
                'channel_ids' => [$channel->id],
            ])
            ->assertRedirect(route('alerts.index'));

        $rule = DmarcAlertRule::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('SPF spike all domains', $rule->name);
        $this->assertSame('spf_fail_rate_spike', $rule->metric);
        $this->assertTrue($rule->is_active);
        $this->assertEquals(2.5, $rule->threshold_multiplier);
        $this->assertTrue($rule->notificationChannels()->whereKey($channel->id)->exists());

        $this->actingAs($user)
            ->patch(route('alerts.rules.update', $rule), [
                'name' => 'DKIM spike only',
                'metric' => 'dkim_fail_rate_spike',
                'domain' => 'Example.COM',
                'threshold_multiplier' => 3.0,
                'min_absolute_increase' => 12,
                'min_messages' => 250,
                'window_minutes' => 720,
                'baseline_days' => 10,
                'cooldown_minutes' => 180,
                'is_active' => 1,
                'channel_ids' => [$channel->id],
            ])
            ->assertRedirect(route('alerts.index'));

        $rule->refresh();
        $this->assertSame('DKIM spike only', $rule->name);
        $this->assertSame('dkim_fail_rate_spike', $rule->metric);
        $this->assertSame('example.com', $rule->domain);
        $this->assertEquals(3.0, $rule->threshold_multiplier);
    }

    public function test_user_can_crud_notification_channels_from_channels_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('alerts.channels.store'), [
                'name' => 'Primary ntfy',
                'type' => 'ntfy',
                'ntfy_url' => 'https://ntfy.sh/dmarc-alerts',
                'ntfy_token' => 'secret',
                'ntfy_ignore_certificate' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('alerts.channels'));

        $channel = DmarcNotificationChannel::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Primary ntfy', $channel->name);
        $this->assertSame('ntfy', $channel->type);
        $this->assertTrue($channel->ntfy_ignore_certificate);

        $this->actingAs($user)
            ->patch(route('alerts.channels.update', $channel), [
                'name' => 'Primary email',
                'type' => 'email',
                'email_to' => 'alerts@example.com',
                'is_active' => 1,
            ])
            ->assertRedirect(route('alerts.channels'));

        $channel->refresh();
        $this->assertSame('Primary email', $channel->name);
        $this->assertSame('email', $channel->type);
        $this->assertSame('alerts@example.com', $channel->email_to);
        $this->assertNull($channel->ntfy_url);

        $this->actingAs($user)
            ->delete(route('alerts.channels.destroy', $channel))
            ->assertRedirect(route('alerts.channels'));

        $this->assertDatabaseMissing('dmarc_notification_channels', ['id' => $channel->id]);
    }

    public function test_alert_rule_updates_do_not_clear_legacy_delivery_fields_during_transition(): void
    {
        $user = User::factory()->create();

        $rule = DmarcAlertRule::query()->create([
            'user_id' => $user->id,
            'name' => 'SPF failure spike alert',
            'metric' => 'spf_fail_rate_spike',
            'notification_email' => 'alerts@example.com',
            'ntfy_url' => 'https://ntfy.sh/existing-topic',
            'ntfy_token' => 'existing-token',
            'ntfy_ignore_certificate' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('alerts.rules.update', $rule), [
                'name' => 'SPF failure spike alert',
                'metric' => 'spf_fail_rate_spike',
                'domain' => null,
                'threshold_multiplier' => 4,
                'min_absolute_increase' => 8,
                'min_messages' => 200,
                'window_minutes' => 1440,
                'baseline_days' => 14,
                'cooldown_minutes' => 720,
                'is_active' => 1,
                'channel_ids' => [],
            ])
            ->assertRedirect(route('alerts.index'));

        $rule->refresh();

        $this->assertSame('alerts@example.com', $rule->notification_email);
        $this->assertSame('https://ntfy.sh/existing-topic', $rule->ntfy_url);
        $this->assertSame('existing-token', $rule->ntfy_token);
        $this->assertTrue($rule->ntfy_ignore_certificate);
        $this->assertEquals(4.0, $rule->threshold_multiplier);
    }

    public function test_dashboard_uses_saved_range_presets(): void
    {
        $user = User::factory()->create([
            'dashboard_range_presets' => ['7d', '180d'],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('7 days')
            ->assertSee('6 months')
            ->assertDontSee('30 days');
    }

    public function test_prune_command_removes_reports_older_than_retention_period(): void
    {
        $user = User::factory()->create([
            'report_retention_days' => 30,
        ]);

        $account = ImapAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Primary Inbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $oldReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'old-report',
            'org_name' => 'Old Sender',
            'email' => 'old@example.com',
            'report_begin_at' => now()->subDays(50),
            'report_end_at' => now()->subDays(45),
            'policy_domain' => 'old.example',
            'raw_xml' => '<feedback />',
        ]);

        $recentReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'recent-report',
            'org_name' => 'Recent Sender',
            'email' => 'recent@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'recent.example',
            'raw_xml' => '<feedback />',
        ]);

        $this->artisan('dmarc:prune-reports')
            ->assertSuccessful();

        $this->assertDatabaseMissing('dmarc_reports', ['id' => $oldReport->id]);
        $this->assertDatabaseHas('dmarc_reports', ['id' => $recentReport->id]);
    }
}

