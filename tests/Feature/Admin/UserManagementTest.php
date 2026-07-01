<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->adminRole());

        return $admin;
    }

    private function adminRole(): Role
    {
        foreach (\Database\Seeders\RolesAndPermissionsSeeder::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('Admin', 'web');
        $role->syncPermissions(\Database\Seeders\RolesAndPermissionsSeeder::permissions());

        return $role;
    }

    public function test_non_permitted_user_gets_403_on_admin_users_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_permitted_admin_can_create_edit_and_assign_roles(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('User', 'web');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Person',
            'email' => 'new-person@example.com',
            'password' => 'password123',
            'is_active' => true,
            'roles' => ['User'],
        ])->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'new-person@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('User'));
        $this->assertTrue($created->is_active);

        $this->actingAs($admin)->patch(route('admin.users.update', $created), [
            'name' => 'Renamed Person',
            'email' => 'new-person@example.com',
            'is_active' => false,
            'roles' => [],
        ])->assertRedirect(route('admin.users.index'));

        $created->refresh();
        $this->assertSame('Renamed Person', $created->name);
        $this->assertFalse($created->is_active);
        $this->assertFalse($created->hasRole('User'));
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertStatus(422);

        $this->assertNotNull($admin->fresh());
    }

    public function test_admin_cannot_disable_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => false,
                'roles' => ['Admin'],
            ])
            ->assertStatus(422);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_active_admin_cannot_be_deleted_by_another_permitted_user(): void
    {
        $onlyAdmin = $this->admin();

        // A separate, active user who holds manage-users but is NOT an Admin
        // themselves, so their own account isn't affected by the guard.
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('manage-users', 'web'));

        $this->actingAs($manager)
            ->delete(route('admin.users.destroy', $onlyAdmin))
            ->assertStatus(422);

        $this->assertNotNull($onlyAdmin->fresh());
    }

    public function test_disabling_a_user_forces_them_out_on_their_next_request(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($target)->get(route('dashboard'))->assertOk();

        $this->actingAs($admin)->patch(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => false,
            'roles' => [],
        ])->assertRedirect(route('admin.users.index'));

        $this->actingAs($target->fresh())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
