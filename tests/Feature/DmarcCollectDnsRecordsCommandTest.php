<?php

namespace Tests\Feature;

use App\Models\DmarcDnsRecordSnapshot;
use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use App\Services\Dns\TxtRecordResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmarcCollectDnsRecordsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_collects_spf_dmarc_and_dkim_records_for_user_domains(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'dns-1',
            'org_name' => 'Receiver',
            'email' => 'receiver@example.net',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => 'example.com',
            'raw_xml' => '<feedback />',
        ]);

        $report->records()->create([
            'source_ip' => '203.0.113.10',
            'message_count' => 20,
            'disposition' => 'none',
            'dkim' => 'pass',
            'dkim_domain' => 'mail.example.com',
            'dkim_selector' => 's1',
            'spf' => 'pass',
            'spf_domain' => 'example.com',
            'header_from' => 'example.com',
        ]);

        app()->bind(TxtRecordResolver::class, fn () => new FakeTxtRecordResolver([
            'example.com' => ['v=spf1 include:_spf.example.net -all'],
            '_dmarc.example.com' => ['v=DMARC1; p=reject; rua=mailto:dmarc@example.com'],
            's1._domainkey.mail.example.com' => ['v=DKIM1; k=rsa; p=ABC123'],
        ]));

        $this->artisan('dmarc:collect-dns-records', ['--user' => $user->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('dmarc_dns_record_snapshots', [
            'user_id' => $user->id,
            'record_type' => 'spf',
            'host' => 'example.com',
            'status' => 'found',
        ]);

        $this->assertDatabaseHas('dmarc_dns_record_snapshots', [
            'user_id' => $user->id,
            'record_type' => 'dmarc',
            'host' => '_dmarc.example.com',
            'status' => 'found',
        ]);

        $this->assertDatabaseHas('dmarc_dns_record_snapshots', [
            'user_id' => $user->id,
            'record_type' => 'dkim',
            'host' => 's1._domainkey.mail.example.com',
            'selector' => 's1',
            'status' => 'found',
        ]);

        $snapshot = DmarcDnsRecordSnapshot::query()
            ->where('user_id', $user->id)
            ->where('record_type', 'dkim')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertSame(['v=DKIM1; k=rsa; p=ABC123'], $snapshot->records);
    }

    public function test_it_collects_a_dkim_snapshot_per_selector_for_the_same_domain(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user);

        $report = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'dns-2',
            'org_name' => 'Receiver',
            'email' => 'receiver@example.net',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => 'example.com',
            'raw_xml' => '<feedback />',
        ]);

        $report->records()->createMany([
            [
                'source_ip' => '203.0.113.10',
                'message_count' => 20,
                'disposition' => 'none',
                'dkim' => 'pass',
                'dkim_domain' => 'mail.example.com',
                'dkim_selector' => 's1',
                'spf' => 'pass',
                'spf_domain' => 'example.com',
                'header_from' => 'example.com',
            ],
            [
                'source_ip' => '203.0.113.11',
                'message_count' => 12,
                'disposition' => 'none',
                'dkim' => 'pass',
                'dkim_domain' => 'mail.example.com',
                'dkim_selector' => 's2',
                'spf' => 'pass',
                'spf_domain' => 'example.com',
                'header_from' => 'example.com',
            ],
        ]);

        app()->bind(TxtRecordResolver::class, fn () => new FakeTxtRecordResolver([
            'example.com' => ['v=spf1 include:_spf.example.net -all'],
            '_dmarc.example.com' => ['v=DMARC1; p=reject; rua=mailto:dmarc@example.com'],
            's1._domainkey.mail.example.com' => ['v=DKIM1; k=rsa; p=S1KEY'],
            's2._domainkey.mail.example.com' => ['v=DKIM1; k=rsa; p=S2KEY'],
        ]));

        $this->artisan('dmarc:collect-dns-records', ['--user' => $user->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('dmarc_dns_record_snapshots', [
            'user_id' => $user->id,
            'record_type' => 'dkim',
            'host' => 's1._domainkey.mail.example.com',
            'selector' => 's1',
            'status' => 'found',
        ]);

        $this->assertDatabaseHas('dmarc_dns_record_snapshots', [
            'user_id' => $user->id,
            'record_type' => 'dkim',
            'host' => 's2._domainkey.mail.example.com',
            'selector' => 's2',
            'status' => 'found',
        ]);
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
}

class FakeTxtRecordResolver implements TxtRecordResolver
{
    /**
     * @param array<string, list<string>> $recordsByHost
     */
    public function __construct(private readonly array $recordsByHost)
    {
    }

    public function resolveTxtRecords(string $host): array
    {
        return $this->recordsByHost[$host] ?? [];
    }
}

