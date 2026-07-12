<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

#[Signature('roles:assign {email? : Email address of the user} {role? : Name of the role to assign} {--remove : Remove the role instead of assigning it}')]
#[Description('Interactively assign or remove a role for an existing user')]
class AssignRoleCommand extends Command
{
    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            return self::FAILURE;
        }

        $remove = $this->resolveAction();
        $roleName = $this->resolveRole($user, $remove);

        if ($roleName === null) {
            return self::FAILURE;
        }

        if ($remove) {
            $user->removeRole($roleName);
            $this->info(sprintf('Removed role "%s" from user "%s" <%s>.', $roleName, $user->name, $user->email));

            return self::SUCCESS;
        }

        $user->assignRole($roleName);
        $this->info(sprintf('Assigned role "%s" to user "%s" <%s>.', $roleName, $user->name, $user->email));

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $email = (string) ($this->argument('email') ?? '');

        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $this->error(sprintf('No user found with email "%s".', $email));

                return null;
            }

            return $user;
        }

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->error('No users exist yet. Create one first with `users:create`.');

            return null;
        }

        $options = $users->mapWithKeys(fn (User $user) => [$user->id => sprintf('%s <%s>', $user->name, $user->email)]);

        $selectedLabel = $this->choice('Select a user', $options->values()->all());
        $selectedId = $options->flip()->get($selectedLabel);

        return $users->firstWhere('id', $selectedId);
    }

    private function resolveAction(): bool
    {
        if ($this->option('remove')) {
            return true;
        }

        if ($this->input->hasParameterOption(['--remove'])) {
            return true;
        }

        return $this->choice('What would you like to do?', ['Assign a role', 'Remove a role'], 'Assign a role') === 'Remove a role';
    }

    private function resolveRole(User $user, bool $remove): ?string
    {
        $roleName = (string) ($this->argument('role') ?? '');

        if ($roleName !== '') {
            if (! Role::query()->where('name', $roleName)->exists()) {
                $this->error(sprintf('No role found named "%s".', $roleName));

                return null;
            }

            return $roleName;
        }

        $availableRoles = $remove
            ? $user->roles->pluck('name')->all()
            : Role::query()->orderBy('name')->pluck('name')->all();

        if ($availableRoles === []) {
            $this->warn($remove ? 'This user has no roles to remove.' : 'No roles exist yet. Create one first with `roles:create`.');

            return null;
        }

        return $this->choice($remove ? 'Select a role to remove' : 'Select a role to assign', $availableRoles);
    }
}
