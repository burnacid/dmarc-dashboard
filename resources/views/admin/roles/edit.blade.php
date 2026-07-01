<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Edit role</h1>
            <p class="mt-2 text-sm text-slate-400">Update permissions for <span class="font-medium text-white">{{ $targetRole->name }}</span>.</p>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 lg:p-8">
            <form method="POST" action="{{ route('admin.roles.update', $targetRole) }}">
                @csrf
                @method('PATCH')
                @include('admin.roles._form', ['targetRole' => $targetRole, 'permissions' => $permissions, 'selectedPermissionNames' => $selectedPermissionNames, 'builtInRoles' => $builtInRoles])
            </form>
        </div>

        @unless (in_array($targetRole->name, $builtInRoles, true))
            <section class="rounded-3xl border border-rose-400/20 bg-rose-400/5 p-6">
                <h2 class="text-sm font-semibold text-rose-300">Delete role</h2>
                <p class="mt-1 text-sm text-slate-400">This permanently removes the <span class="font-medium text-white">{{ $targetRole->name }}</span> role from all assigned users.</p>
                <form method="POST" action="{{ route('admin.roles.destroy', $targetRole) }}" class="mt-4" onsubmit="return confirm('Delete this role?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-400/20">
                        Delete role
                    </button>
                </form>
            </section>
        @endunless
    </div>
</x-app-layout>
