<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            'manage-users',
            'manage-roles',
            'manage-organizations',
            'manage-domains',
            'manage-imap-accounts',
            'manage-alert-rules',
            'manage-notification-channels',
            'view-admin-tools',
        ];
    }

    /**
     * Seed the default permissions/roles and grant the Admin role to any
     * user that already satisfies the legacy `is_admin` column check.
     */
    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('Admin', 'web');
        $admin->syncPermissions(self::permissions());

        Role::findOrCreate('User', 'web');

        User::query()->where('is_admin', true)->each(function (User $user): void {
            if (! $user->hasRole('Admin')) {
                $user->assignRole('Admin');
            }
        });
    }
}
