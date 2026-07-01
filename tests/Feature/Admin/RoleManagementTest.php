<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_role_grants_exactly_its_assigned_permission(): void
    {
        $roleManager = User::factory()->create();
        $roleManager->givePermissionTo(Permission::findOrCreate('manage-roles', 'web'));

        Permission::findOrCreate('manage-domains', 'web');
        Permission::findOrCreate('manage-organizations', 'web');

        $this->actingAs($roleManager)->post(route('admin.roles.store'), [
            'name' => 'Domain Curator',
            'permissions' => ['manage-domains'],
        ])->assertRedirect(route('admin.roles.index'));

        $curator = User::factory()->create();
        $curator->assignRole('Domain Curator');

        $this->actingAs($curator)->get(route('admin.domains.index'))->assertOk();
        $this->actingAs($curator)->get(route('admin.organizations.index'))->assertForbidden();
    }

    public function test_built_in_roles_cannot_be_deleted_or_renamed(): void
    {
        $roleManager = User::factory()->create();
        $roleManager->givePermissionTo(Permission::findOrCreate('manage-roles', 'web'));
        $adminRole = Role::findOrCreate('Admin', 'web');

        $this->actingAs($roleManager)
            ->delete(route('admin.roles.destroy', $adminRole))
            ->assertStatus(422);

        $this->actingAs($roleManager)
            ->patch(route('admin.roles.update', $adminRole), [
                'name' => 'Renamed',
                'permissions' => [],
            ])
            ->assertStatus(422);

        $this->assertNotNull($adminRole->fresh());
        $this->assertSame('Admin', $adminRole->fresh()->name);
    }
}
