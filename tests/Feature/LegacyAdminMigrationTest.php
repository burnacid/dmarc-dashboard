<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyAdminMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_is_admin_flag_is_granted_the_admin_role(): void
    {
        $legacyAdmin = User::factory()->create(['is_admin' => true]);
        $plainUser = User::factory()->create();

        (new RolesAndPermissionsSeeder())->run();

        $this->assertTrue($legacyAdmin->fresh()->hasRole('Admin'));
        $this->assertFalse($plainUser->fresh()->hasRole('Admin'));
    }

    public function test_admin_emails_config_match_is_not_granted_a_role_but_still_grants_live_access(): void
    {
        $user = User::factory()->create(['email' => 'config-admin@example.com']);
        config(['app.admin_emails' => ['config-admin@example.com']]);

        (new RolesAndPermissionsSeeder())->run();

        $this->assertFalse($user->fresh()->hasRole('Admin'));
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_seeder_is_idempotent(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        (new RolesAndPermissionsSeeder())->run();
        (new RolesAndPermissionsSeeder())->run();

        $this->assertSame(1, $admin->fresh()->roles()->where('name', 'Admin')->count());
    }
}
