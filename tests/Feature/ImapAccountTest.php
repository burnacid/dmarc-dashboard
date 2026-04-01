<?php

namespace Tests\Feature;

use App\Models\ImapAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImapAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_imap_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('imap-accounts.store'), [
            'name' => 'Primary Inbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret-app-password',
            'folder' => 'INBOX',
            'processed_folder' => 'DMARC/Processed',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('imap-accounts.index'));

        $this->assertDatabaseHas('imap_accounts', [
            'user_id' => $user->id,
            'name' => 'Primary Inbox',
            'host' => 'imap.example.com',
            'username' => 'reports@example.com',
            'processed_folder' => 'DMARC/Processed',
        ]);
    }

    public function test_authenticated_user_can_update_imap_account_without_replacing_password(): void
    {
        $user = User::factory()->create();
        $account = ImapAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Primary Inbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret-app-password',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $originalPassword = $account->getRawOriginal('password');

        $response = $this->actingAs($user)->put(route('imap-accounts.update', $account), [
            'name' => 'Updated Inbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => '',
            'folder' => 'Reports',
            'processed_folder' => 'Processed/DMARC',
            'search_criteria' => 'ALL',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('imap-accounts.index'));

        $account->refresh();

        $this->assertSame('Updated Inbox', $account->name);
        $this->assertSame('Reports', $account->folder);
        $this->assertSame('Processed/DMARC', $account->processed_folder);
        $this->assertSame('ALL', $account->search_criteria);
        $this->assertFalse($account->is_active);
        $this->assertSame($originalPassword, $account->getRawOriginal('password'));
    }

    public function test_user_cannot_manage_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = ImapAccount::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign Inbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret-app-password',
            'folder' => 'INBOX',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('imap-accounts.edit', $account))
            ->assertNotFound();
    }
}

