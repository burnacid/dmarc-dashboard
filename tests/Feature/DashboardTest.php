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
            ->assertDontSee('Old Sender');

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
            ->assertDontSee('Old Sender');
    }

    public function test_dashboard_can_filter_by_domain_and_shows_user_domain_options_only(): void
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

        $alphaReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'alpha-report',
            'org_name' => 'Alpha Sender',
            'email' => 'alpha@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'alpha.example',
            'raw_xml' => '<feedback />',
        ]);

        $betaReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'beta-report',
            'org_name' => 'Beta Sender',
            'email' => 'beta@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'beta.example',
            'raw_xml' => '<feedback />',
        ]);

        $foreignReport = DmarcReport::query()->create([
            'imap_account_id' => $otherAccount->id,
            'external_report_id' => 'foreign-report',
            'org_name' => 'Foreign Sender',
            'email' => 'foreign@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'foreign.example',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $alphaReport->id,
            'source_ip' => '203.0.113.10',
            'message_count' => 10,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => 'alpha.example',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $betaReport->id,
            'source_ip' => '203.0.113.11',
            'message_count' => 12,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => 'beta.example',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $foreignReport->id,
            'source_ip' => '203.0.113.99',
            'message_count' => 25,
            'disposition' => 'reject',
            'dkim' => 'fail',
            'spf' => 'fail',
            'header_from' => 'foreign.example',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('All domains')
            ->assertSee('alpha.example')
            ->assertSee('beta.example')
            ->assertDontSee('foreign.example');

        $this->actingAs($user)
            ->get(route('dashboard', ['domain' => 'alpha.example']))
            ->assertOk()
            ->assertSee('Alpha Sender')
            ->assertDontSee('Beta Sender');
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

    public function test_report_detail_page_shows_parsed_dkim_and_spf_domains_per_record(): void
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

        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'detail-report',
            'org_name' => 'Detail Sender',
            'email' => 'detail@example.com',
            'report_begin_at' => now()->subDays(2),
            'report_end_at' => now()->subDay(),
            'policy_domain' => 'example.com',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $report->id,
            'source_ip' => '203.0.113.44',
            'message_count' => 8,
            'disposition' => 'none',
            'dkim' => 'pass',
            'dkim_domain' => 'mail.example.com',
            'spf' => 'pass',
            'spf_domain' => 'spf.example.net',
            'header_from' => 'example.com',
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('DKIM result')
            ->assertSee('mail.example.com')
            ->assertSee('SPF result')
            ->assertSee('spf.example.net');
    }
}

