<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const BUILT_IN_ROLES = ['Admin', 'User'];

    public function index(): View
    {
        $roles = Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'builtInRoles' => self::BUILT_IN_ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'targetRole' => new Role(),
            'permissions' => Permission::query()->orderBy('name')->get(),
            'selectedPermissionNames' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions']);

        return to_route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'targetRole' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'selectedPermissionNames' => $role->permissions->pluck('name')->all(),
            'builtInRoles' => self::BUILT_IN_ROLES,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        abort_if(
            in_array($role->name, self::BUILT_IN_ROLES, true) && $validated['name'] !== $role->name,
            422,
            'Cannot rename a built-in role.'
        );

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return to_route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::BUILT_IN_ROLES, true), 422, 'Cannot delete a built-in role.');

        $role->delete();

        return to_route('admin.roles.index')->with('status', 'Role deleted.');
    }
}
