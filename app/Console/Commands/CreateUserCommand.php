<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

#[Signature('users:create {name? : The user\'s display name} {email? : The user\'s email address} {--password= : Plain text password (prompted if omitted)} {--role=* : Role name to assign (repeatable)} {--inactive : Create the user as inactive}')]
#[Description('Interactively create a new user and optionally assign roles')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $name = $this->resolveName();
        $email = $this->resolveEmail();
        $password = $this->resolvePassword();
        $roleNames = $this->resolveRoles();
        $isActive = $this->resolveIsActive();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_active' => $isActive,
        ]);

        if ($roleNames !== []) {
            $user->syncRoles(Role::query()->whereIn('name', $roleNames)->get());
        }

        $this->newLine();
        $this->info(sprintf(
            'User "%s" <%s> created%s.',
            $user->name,
            $user->email,
            $roleNames !== [] ? ' with role(s): '.implode(', ', $roleNames) : ' with no roles'
        ));

        return self::SUCCESS;
    }

    private function resolveName(): string
    {
        $name = (string) ($this->argument('name') ?? '');

        while (true) {
            if ($name === '') {
                $name = (string) $this->ask('User name');
            }

            $validator = Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:255']]);

            if ($validator->passes()) {
                return $name;
            }

            $this->error($validator->errors()->first('name'));
            $name = '';
        }
    }

    private function resolveEmail(): string
    {
        $email = (string) ($this->argument('email') ?? '');

        while (true) {
            if ($email === '') {
                $email = (string) $this->ask('User email address');
            }

            $validator = Validator::make(
                ['email' => $email],
                ['email' => ['required', 'string', 'email', 'max:255', 'unique:users,email']]
            );

            if ($validator->passes()) {
                return $email;
            }

            $this->error($validator->errors()->first('email'));
            $email = '';
        }
    }

    private function resolvePassword(): string
    {
        $providedViaOption = (string) ($this->option('password') ?? '');

        if ($providedViaOption !== '') {
            $validator = Validator::make(
                ['password' => $providedViaOption],
                ['password' => ['required', 'string', 'min:8']]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first('password'));

                return $this->promptForPassword();
            }

            return $providedViaOption;
        }

        return $this->promptForPassword();
    }

    private function promptForPassword(): string
    {
        while (true) {
            $password = (string) $this->secret('Password (min 8 characters)');

            $validator = Validator::make(['password' => $password], ['password' => ['required', 'string', 'min:8']]);

            if ($validator->fails()) {
                $this->error($validator->errors()->first('password'));

                continue;
            }

            $confirmation = (string) $this->secret('Confirm password');

            if ($confirmation !== $password) {
                $this->error('Passwords do not match.');

                continue;
            }

            return $password;
        }
    }

    /** @return list<string> */
    private function resolveRoles(): array
    {
        $roleNames = array_values(array_unique(array_filter((array) $this->option('role'))));

        if ($roleNames !== []) {
            $validator = Validator::make(
                ['roles' => $roleNames],
                ['roles.*' => ['string', 'exists:roles,name']]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                return $this->promptForRoles();
            }

            return $roleNames;
        }

        return $this->promptForRoles();
    }

    /** @return list<string> */
    private function promptForRoles(): array
    {
        $availableRoles = Role::query()->orderBy('name')->pluck('name')->all();

        if ($availableRoles === []) {
            $this->warn('No roles exist yet. Skipping role assignment.');

            return [];
        }

        if (! $this->confirm('Assign role(s) to this user?', true)) {
            return [];
        }

        $selected = $this->choice('Select role(s) (comma-separated for multiple)', $availableRoles, null, null, true);

        return array_values($selected);
    }

    private function resolveIsActive(): bool
    {
        if ($this->option('inactive')) {
            return false;
        }

        return $this->confirm('Activate this user immediately?', true);
    }
}
