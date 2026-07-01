<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'role' => trim((string) $request->input('role', '')),
            'is_active' => trim((string) $request->input('is_active', '')),
        ];

        $users = User::query()
            ->with('roles')
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($sub) use ($filters): void {
                $sub->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('email', 'like', '%'.$filters['q'].'%');
            }))
            ->when($filters['role'] !== '', fn ($query) => $query->role($filters['role']))
            ->when($filters['is_active'] !== '', fn ($query) => $query->where('is_active', $filters['is_active'] === '1'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'filters', 'roles'));
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'targetUser' => new User(['is_active' => true]),
            'roles' => Role::query()->orderBy('name')->get(),
            'selectedRoleNames' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => $validated['is_active'],
        ]);

        $user->syncRoles($validated['roles']);

        return to_route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'targetUser' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
            'selectedRoleNames' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $this->guardAgainstLockout($request->user(), $user, $validated['roles'], $validated['is_active']);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ];

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        $user->update($attributes);
        $user->syncRoles($validated['roles']);

        return to_route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'You cannot delete your own account.');
        $this->ensureNotLastActiveAdmin($user);

        $user->delete();

        return to_route('admin.users.index')->with('status', 'User removed.');
    }

    /**
     * @param  list<string>  $incomingRoleNames
     */
    private function guardAgainstLockout(User $actor, User $target, array $incomingRoleNames, bool $incomingIsActive): void
    {
        $wasAdmin = $target->hasRole('Admin');
        $willBeAdmin = in_array('Admin', $incomingRoleNames, true);

        if ($actor->id === $target->id) {
            abort_if(! $incomingIsActive, 422, 'You cannot disable your own account.');
            abort_if($wasAdmin && ! $willBeAdmin, 422, 'You cannot remove your own Admin role.');
        }

        if ($wasAdmin && (! $willBeAdmin || ! $incomingIsActive)) {
            $this->ensureNotLastActiveAdmin($target);
        }
    }

    private function ensureNotLastActiveAdmin(User $target): void
    {
        if (! $target->hasRole('Admin')) {
            return;
        }

        $otherActiveAdmins = User::role('Admin')
            ->where('is_active', true)
            ->whereKeyNot($target->id)
            ->count();

        abort_if($otherActiveAdmins === 0, 422, 'Cannot remove the last active admin.');
    }
}
