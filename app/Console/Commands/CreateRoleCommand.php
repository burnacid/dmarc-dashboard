<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Signature('roles:create {name? : The role name} {--permission=* : Permission name to grant (repeatable)}')]
#[Description('Interactively create a new role and select its permissions')]
class CreateRoleCommand extends Command
{
    public function handle(): int
    {
        $name = $this->resolveName();
        $permissionNames = $this->resolvePermissions();

        $role = Role::create(['name' => $name, 'guard_name' => 'web']);

        if ($permissionNames !== []) {
            $role->syncPermissions(Permission::query()->whereIn('name', $permissionNames)->get());
        }

        $this->newLine();
        $this->info(sprintf(
            'Role "%s" created%s.',
            $role->name,
            $permissionNames !== [] ? ' with permission(s): '.implode(', ', $permissionNames) : ' with no permissions'
        ));

        return self::SUCCESS;
    }

    private function resolveName(): string
    {
        $name = (string) ($this->argument('name') ?? '');

        while (true) {
            if ($name === '') {
                $name = (string) $this->ask('Role name');
            }

            $validator = Validator::make(
                ['name' => $name],
                ['name' => ['required', 'string', 'max:255', 'unique:roles,name']]
            );

            if ($validator->passes()) {
                return $name;
            }

            $this->error($validator->errors()->first('name'));
            $name = '';
        }
    }

    /** @return list<string> */
    private function resolvePermissions(): array
    {
        $permissionNames = array_values(array_unique(array_filter((array) $this->option('permission'))));

        if ($permissionNames !== []) {
            $validator = Validator::make(
                ['permissions' => $permissionNames],
                ['permissions.*' => ['string', 'exists:permissions,name']]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                return $this->promptForPermissions();
            }

            return $permissionNames;
        }

        return $this->promptForPermissions();
    }

    /** @return list<string> */
    private function promptForPermissions(): array
    {
        $availablePermissions = Permission::query()->orderBy('name')->pluck('name')->all();

        if ($availablePermissions === []) {
            $this->warn('No permissions exist yet. Skipping permission assignment.');

            return [];
        }

        if (! $this->confirm('Grant permission(s) to this role?', true)) {
            return [];
        }

        $selected = $this->choice(
            'Select permission(s) (comma-separated for multiple)',
            $availablePermissions,
            null,
            null,
            true
        );

        return array_values($selected);
    }
}
