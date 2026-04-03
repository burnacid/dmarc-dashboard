<?php

namespace Tests\Feature;

use App\Models\DmarcRecord;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmarcReportIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_requires_authentication(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_reports_index_shows_newest_reports_first_and_only_for_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = $this->makeAccount($user, 'Primary Inbox');
        $otherAccount = $this->makeAccount($otherUser, 'Other Inbox');

        $older = $this->makeReport($account, 'older-report', 'Older Org', 'older.example', now()->subDays(3));
        $newer = $this->makeReport($account, 'newer-report', 'Newer Org', 'newer.example', now()->subDay());
        $this->makeReport($otherAccount, 'foreign-report', 'Foreign Org', 'foreign.example', now());

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('All DMARC reports')
            ->assertSee('Newer Org')
            ->assertSee('Older Org')
            ->assertDontSee('Foreign Org')
            ->assertSeeInOrder(['Newer Org', 'Older Org']);

        $this->assertNotNull($older);
        $this->assertNotNull($newer);
    }

    public function test_reports_index_filters_by_account_domain_org_and_report_id(): void
    {
        $user = User::factory()->create();

        $primaryAccount = $this->makeAccount($user, 'Primary Inbox');
        $secondaryAccount = $this->makeAccount($user, 'Secondary Inbox');

        $matching = $this->makeReport($primaryAccount, 'rep-123', 'Google', 'example.com', now()->subDay());
        $this->makeReport($secondaryAccount, 'rep-999', 'Microsoft', 'example.net', now()->subDays(2));

        $response = $this->actingAs($user)->get(route('reports.index', [
            'account_id' => $primaryAccount->id,
            'domain' => 'example.com',
            'org' => 'Google',
            'report_id' => 'rep-123',
        ]));

        $response->assertOk()
            ->assertSee('Google')
            ->assertSee('example.com')
            ->assertSee('DKIM pass')
            ->assertSee('SPF pass')
            ->assertDontSee('Microsoft')
            ->assertSee(route('reports.show', $matching), false);
    }

    public function test_reports_index_domain_filter_is_populated_from_discovered_user_domains_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = $this->makeAccount($user, 'Primary Inbox');
        $otherAccount = $this->makeAccount($otherUser, 'Other Inbox');

        $this->makeReport($account, 'rep-1', 'Google', 'alpha.example', now()->subDay());
        $this->makeReport($account, 'rep-2', 'Google', 'beta.example', now()->subHours(12));
        $this->makeReport($otherAccount, 'rep-3', 'Yahoo', 'foreign.example', now()->subHours(6));

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('All domains')
            ->assertSee('alpha.example')
            ->assertSee('beta.example')
            ->assertDontSee('foreign.example');
    }

    public function test_reports_index_filters_by_preset_and_custom_time_ranges(): void
    {
        $user = User::factory()->create();
        $account = $this->makeAccount($user, 'Primary Inbox');

        $this->makeReport($account, 'recent-report', 'Recent Org', 'recent.example', now()->subDay());
        $this->makeReport($account, 'old-report', 'Old Org', 'old.example', now()->subDays(45));

        $this->actingAs($user)
            ->get(route('reports.index', ['range' => '7d']))
            ->assertOk()
            ->assertSee('Recent Org')
            ->assertDontSee('Old Org');

        $this->actingAs($user)
            ->get(route('reports.index', [
                'range' => 'custom',
                'from' => now()->subDays(50)->format('Y-m-d'),
                'to' => now()->subDays(40)->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Old Org')
            ->assertDontSee('Recent Org');
    }

    public function test_reports_index_paginates_results(): void
    {
        $user = User::factory()->create();
        $account = $this->makeAccount($user, 'Primary Inbox');

        foreach (range(1, 12) as $index) {
            $this->makeReport($account, 'report-'.$index, 'Org '.$index, 'domain'.$index.'.example', now()->subHours($index));
        }

        $response = $this->actingAs($user)->get(route('reports.index', ['per_page' => 10]));

        $response->assertOk()
            ->assertSee('Org 1')
            ->assertSee('Org 10')
            ->assertDontSee('Org 11')
            ->assertSee('?per_page=10&amp;page=2', false);
    }

    private function makeAccount(User $user, string $name): ImapAccount
    {
        return ImapAccount::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'secret',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);
    }

    private function makeReport(ImapAccount $account, string $reportId, string $org, string $domain, CarbonInterface $reportedAt): DmarcReport
    {
        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => $reportId,
            'org_name' => $org,
            'email' => strtolower($org).'@example.com',
            'report_begin_at' => $reportedAt->copy()->subDay(),
            'report_end_at' => $reportedAt,
            'policy_domain' => $domain,
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $report->id,
            'source_ip' => '203.0.113.10',
            'message_count' => 10,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
            'header_from' => $domain,
        ]);

        return $report;
    }
}

