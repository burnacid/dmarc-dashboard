<?php

namespace Tests\Feature\Admin;

use App\Models\DmarcRecord;
use App\Models\DmarcReport;
use App\Models\Domain;
use App\Models\ImapAccount;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrganizationDomainTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(
            Permission::findOrCreate('manage-organizations', 'web'),
            Permission::findOrCreate('manage-domains', 'web'),
        );

        return $admin;
    }

    public function test_non_permitted_user_gets_403_on_admin_organizations_and_domains(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.organizations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.domains.index'))->assertForbidden();
    }

    public function test_admin_can_create_organization_and_assign_domain(): void
    {
        $admin = $this->admin();
        $domain = Domain::create(['name' => 'example.com']);

        $this->actingAs($admin)->post(route('admin.organizations.store'), [
            'name' => 'Acme Corp',
        ])->assertRedirect(route('admin.organizations.index'));

        $organization = Organization::query()->where('name', 'Acme Corp')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.domains.update', $domain), [
            'organization_id' => $organization->id,
        ])->assertRedirect();

        $this->assertSame($organization->id, $domain->fresh()->organization_id);
    }

    public function test_deleting_organization_unassigns_its_domains(): void
    {
        $admin = $this->admin();
        $organization = Organization::create(['name' => 'Acme Corp']);
        $domain = Domain::create(['name' => 'example.com', 'organization_id' => $organization->id]);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.destroy', $organization))
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertNull($domain->fresh()->organization_id);
    }

    public function test_domain_register_normalizes_and_is_idempotent(): void
    {
        $first = Domain::register('Newly-Seen.example ');
        $second = Domain::register('newly-seen.example');

        $this->assertSame('newly-seen.example', $first->name);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('domains', 1);
    }

    public function test_organization_filter_narrows_dashboard_to_its_domains(): void
    {
        config(['app.shared_mode' => true]);

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

        $inOrgReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'report-in-org',
            'org_name' => 'Google',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => 'in-org.example',
            'raw_xml' => '<feedback />',
        ]);

        $outOfOrgReport = DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'report-out-of-org',
            'org_name' => 'Google',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => 'out-of-org.example',
            'raw_xml' => '<feedback />',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $inOrgReport->id,
            'source_ip' => '203.0.113.10',
            'message_count' => 5,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
        ]);

        DmarcRecord::query()->create([
            'dmarc_report_id' => $outOfOrgReport->id,
            'source_ip' => '203.0.113.11',
            'message_count' => 5,
            'disposition' => 'none',
            'dkim' => 'pass',
            'spf' => 'pass',
        ]);

        $organization = Organization::create(['name' => 'Acme Corp']);
        Domain::create(['name' => 'in-org.example', 'organization_id' => $organization->id]);
        Domain::create(['name' => 'out-of-org.example']);

        $this->actingAs($user)->post(route('filters.organization.update'), [
            'organization_id' => $organization->id,
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('in-org.example')
            ->assertDontSee('out-of-org.example');
    }
}
