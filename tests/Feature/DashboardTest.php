<?php

namespace Tests\Feature;

use App\Models\DmarcRecord;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_user_metrics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

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

        $account->forceFill(['last_polled_at' => now()])->save();

        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'report-1',
            'org_name' => 'Google',
            'email' => 'noreply@google.com',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => 'example.com',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $report->id,
            'source_ip' => '203.0.113.10',
            'message_count' => 24,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => 'example.com',
        ]);

        ImapAccount::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Inbox',
            'host' => 'imap.other.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'other@example.com',
            'password' => 'secret',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('DMARC monitoring dashboard')
            ->assertSee('Primary Inbox')
            ->assertSee('Google')
            ->assertSee('Records per domain')
            ->assertSee('Authentication results')
            ->assertSee('DKIM pass')
            ->assertSee('203.0.113.10')
            ->assertDontSee('Other Inbox');
    }

    public function test_dashboard_defaults_to_one_month_but_can_expand_range(): void
    {
        $user = User::factory()->create();

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

        $currentReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'current-report',
            'org_name' => 'Current Sender',
            'email' => 'current@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'current.example',
            'raw_xml' => '<feedback />',
        ]);

        $oldReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'old-report',
            'org_name' => 'Old Sender',
            'email' => 'old@example.com',
            'report_begin_at' => now()->subDays(70),
            'report_end_at' => now()->subDays(65),
            'policy_domain' => 'old.example',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $currentReport->id,
            'source_ip' => '203.0.113.11',
            'message_count' => 12,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => 'current.example',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $oldReport->id,
            'source_ip' => '203.0.113.99',
            'message_count' => 20,
            'disposition' => 'reject',
            'dkim' => 'fail',
            'spf' => 'fail',
            'header_from' => 'old.example',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Current Sender')
            ->assertSee('current.example')
            ->assertDontSee('Old Sender')
            ->assertDontSee('old.example');

        $this->actingAs($user)
            ->get(route('dashboard', ['range' => '90d']))
            ->assertOk()
            ->assertSee('Old Sender')
            ->assertSee('old.example');
    }

    public function test_dashboard_can_filter_by_custom_date_range(): void
    {
        $user = User::factory()->create();

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

        $oldReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'old-report',
            'org_name' => 'Old Sender',
            'email' => 'old@example.com',
            'report_begin_at' => now()->subDays(15),
            'report_end_at' => now()->subDays(14),
            'policy_domain' => 'old.example',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $recentReport->id,
            'source_ip' => '203.0.113.11',
            'message_count' => 12,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => 'recent.example',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $oldReport->id,
            'source_ip' => '203.0.113.99',
            'message_count' => 20,
            'disposition' => 'reject',
            'dkim' => 'fail',
            'spf' => 'fail',
            'header_from' => 'old.example',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', [
                'range' => 'custom',
                'from' => now()->subDays(3)->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Recent Sender')
            ->assertSee('recent.example')
            ->assertDontSee('Old Sender')
            ->assertDontSee('old.example');
    }

    public function test_user_can_view_their_original_report_but_not_someone_elses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownAccount = ImapAccount::query()->create([
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

        $otherAccount = ImapAccount::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Inbox',
            'host' => 'imap.other.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'other@example.com',
            'password' => 'secret',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $ownReport = DmarcReport::query()->create([
            'imap_account_id' => $ownAccount->id,
            'external_report_id' => 'own-report',
            'org_name' => 'Own Sender',
            'email' => 'own@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'own.example',
            'raw_xml' => '<feedback><report_metadata><report_id>own-report</report_id></report_metadata></feedback>',
        ]);

        $otherReport = DmarcReport::query()->create([
            'imap_account_id' => $otherAccount->id,
            'external_report_id' => 'other-report',
            'org_name' => 'Other Sender',
            'email' => 'other@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'other.example',
            'raw_xml' => '<feedback><report_metadata><report_id>other-report</report_id></report_metadata></feedback>',
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $ownReport))
            ->assertOk()
            ->assertSee('Original raw XML')
            ->assertSee('own-report');

        $this->actingAs($user)
            ->get(route('reports.show', $otherReport))
            ->assertNotFound();
    }
}

