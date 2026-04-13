<?php

namespace Tests\Feature;

use App\Models\DmarcAlertRule;
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
                'alerts_spf_spike_enabled' => 1,
                'alerts_spf_spike_domain' => 'Example.COM',
                'alerts_spf_spike_threshold_multiplier' => 2.5,
                'alerts_spf_spike_min_absolute_increase' => 10,
                'alerts_spf_spike_min_messages' => 300,
                'alerts_spf_spike_window_minutes' => 1440,
                'alerts_spf_spike_baseline_days' => 21,
                'alerts_spf_spike_cooldown_minutes' => 720,
                'alerts_spf_spike_notification_email' => 'alerts@example.com',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame(180, $user->report_retention_days);
        $this->assertSame(['7d', '30d', '180d'], $user->dashboard_range_presets);

        $rule = DmarcAlertRule::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($rule);
        $this->assertSame('spf_fail_rate_spike', $rule->metric);
        $this->assertTrue($rule->is_active);
        $this->assertSame('example.com', $rule->domain);
        $this->assertSame('alerts@example.com', $rule->notification_email);
        $this->assertEquals(2.5, $rule->threshold_multiplier);
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

