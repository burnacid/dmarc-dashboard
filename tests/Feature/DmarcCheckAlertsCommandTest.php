<?php

namespace Tests\Feature;

use App\Models\DmarcAlertRule;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use App\Notifications\SpfFailRateSpikeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DmarcCheckAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_command_triggers_spf_spike_notification_and_event(): void
    {
        Carbon::setTestNow('2026-04-08 12:00:00');
        Notification::fake();

        $user = User::factory()->create();
        $account = $this->createAccount($user);

        $rule = DmarcAlertRule::query()->create([
            'user_id' => $user->id,
            'metric' => 'spf_fail_rate_spike',
            'domain' => 'example.com',
            'threshold_multiplier' => 2.0,
            'min_absolute_increase' => 8.0,
            'min_messages' => 100,
            'window_minutes' => 1440,
            'baseline_days' => 14,
            'cooldown_minutes' => 720,
            'notification_email' => 'alerts@example.com',
            'is_active' => true,
        ]);

        // Baseline: 10% fail rate (100/1000)
        $this->createReportWithRecord($account, now()->subDays(3), 'baseline-pass', 'pass', 900);
        $this->createReportWithRecord($account, now()->subDays(3), 'baseline-fail', 'fail', 100);

        // Current window: 30% fail rate (90/300)
        $this->createReportWithRecord($account, now()->subHours(6), 'current-pass', 'pass', 210);
        $this->createReportWithRecord($account, now()->subHours(6), 'current-fail', 'fail', 90);

        $this->artisan('dmarc:check-alerts')
            ->assertSuccessful()
            ->expectsOutput('DMARC alert evaluation complete. 1 alert(s) triggered.');

        $this->assertDatabaseCount('dmarc_alert_events', 1);
        $this->assertDatabaseHas('dmarc_alert_events', [
            'dmarc_alert_rule_id' => $rule->id,
            'current_total_messages' => 300,
            'current_spf_fail_messages' => 90,
            'baseline_total_messages' => 1000,
            'baseline_spf_fail_messages' => 100,
        ]);

        Notification::assertSentOnDemand(SpfFailRateSpikeNotification::class, function ($notification, $channels, $notifiable) {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'alerts@example.com';
        });

        Carbon::setTestNow();
    }

    public function test_alert_command_respects_cooldown_and_does_not_send_duplicate_notification(): void
    {
        Carbon::setTestNow('2026-04-08 12:00:00');
        Notification::fake();

        $user = User::factory()->create();
        $account = $this->createAccount($user);

        DmarcAlertRule::query()->create([
            'user_id' => $user->id,
            'metric' => 'spf_fail_rate_spike',
            'domain' => 'example.com',
            'threshold_multiplier' => 2.0,
            'min_absolute_increase' => 8.0,
            'min_messages' => 100,
            'window_minutes' => 1440,
            'baseline_days' => 14,
            'cooldown_minutes' => 720,
            'is_active' => true,
        ]);

        $this->createReportWithRecord($account, now()->subDays(3), 'baseline-pass', 'pass', 900);
        $this->createReportWithRecord($account, now()->subDays(3), 'baseline-fail', 'fail', 100);
        $this->createReportWithRecord($account, now()->subHours(6), 'current-pass', 'pass', 210);
        $this->createReportWithRecord($account, now()->subHours(6), 'current-fail', 'fail', 90);

        $this->artisan('dmarc:check-alerts')->assertSuccessful();
        $this->artisan('dmarc:check-alerts')->assertSuccessful();

        $this->assertDatabaseCount('dmarc_alert_events', 1);
        Notification::assertSentOnDemandTimes(SpfFailRateSpikeNotification::class, 1);

        Carbon::setTestNow();
    }

    private function createAccount(User $user): ImapAccount
    {
        return ImapAccount::query()->create([
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
    }

    private function createReportWithRecord(ImapAccount $account, Carbon $reportEndAt, string $externalId, string $spfResult, int $messageCount): void
    {
        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => $externalId,
            'org_name' => 'Sender',
            'email' => 'sender@example.com',
            'report_begin_at' => $reportEndAt->copy()->subDay(),
            'report_end_at' => $reportEndAt,
            'policy_domain' => 'example.com',
            'raw_xml' => '<feedback />',
        ]);

        $report->records()->create([
            'source_ip' => '203.0.113.10',
            'message_count' => $messageCount,
            'disposition' => $spfResult === 'fail' ? 'quarantine' : 'none',
            'dkim' => 'pass',
            'spf' => $spfResult,
            'header_from' => 'example.com',
        ]);
    }
}

