<?php

namespace Tests\Feature;

use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SharedModeTest extends TestCase
{
    use RefreshDatabase;

    private function createAccountWithReport(User $owner, string $accountName, string $domain): ImapAccount
    {
        $account = ImapAccount::query()->create([
            'user_id' => $owner->id,
            'name' => $accountName,
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        DmarcReport::query()->create([
            'imap_account_id' => $account->id,
            'external_report_id' => 'report-'.$account->id,
            'org_name' => 'Google',
            'email' => 'noreply@google.com',
            'report_begin_at' => now()->subDay(),
            'report_end_at' => now(),
            'policy_domain' => $domain,
            'raw_xml' => '<feedback />',
        ]);

        return $account;
    }

    public function test_shared_mode_off_keeps_users_isolated(): void
    {
        config(['app.shared_mode' => false]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createAccountWithReport($user, 'My Own Inbox', 'mine.example');
        $this->createAccountWithReport($otherUser, 'Foreign Inbox', 'foreign.example');

        $this->actingAs($user)
            ->get(route('imap-accounts.index'))
            ->assertOk()
            ->assertSee('My Own Inbox')
            ->assertDontSee('Foreign Inbox');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('mine.example')
            ->assertDontSee('foreign.example');
    }

    public function test_shared_mode_on_exposes_all_accounts_and_reports_to_every_user(): void
    {
        config(['app.shared_mode' => true]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createAccountWithReport($user, 'My Own Inbox', 'mine.example');
        $foreignAccount = $this->createAccountWithReport($otherUser, 'Foreign Inbox', 'foreign.example');

        $this->actingAs($user)
            ->get(route('imap-accounts.index'))
            ->assertOk()
            ->assertSee('Foreign Inbox');

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('foreign.example');

        // Shared mode makes another user's account *visible*, but editing it
        // still requires ownership or the manage-imap-accounts permission.
        $this->actingAs($user)
            ->get(route('imap-accounts.edit', $foreignAccount))
            ->assertNotFound();
    }

    public function test_shared_mode_on_allows_manage_permission_to_edit_others_accounts(): void
    {
        config(['app.shared_mode' => true]);

        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('manage-imap-accounts', 'web'));

        $owner = User::factory()->create();
        $account = $this->createAccountWithReport($owner, 'Foreign Inbox', 'foreign.example');

        $this->actingAs($manager)
            ->get(route('imap-accounts.edit', $account))
            ->assertOk();
    }
}
