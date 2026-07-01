<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Roles</h1>
                <p class="mt-2 text-sm text-slate-400">Define permission sets and assign them to users.</p>
            </div>

            <a href="{{ route('admin.roles.create') }}" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                Add role
            </a>
        </div>
    </x-slot>

    <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <div class="overflow-x-auto rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-left text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Permissions</th>
                        <th class="px-4 py-3 font-medium">Users</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-4 py-3 align-top font-medium text-white">
                                {{ $role->name }}
                                @if (in_array($role->name, $builtInRoles, true))
                                    <span class="ml-2 rounded-full bg-slate-400/10 px-2 py-0.5 text-xs text-slate-400">built-in</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-300">{{ $role->permissions_count }}</td>
                            <td class="px-4 py-3 align-top text-slate-300">{{ $role->users_count }}</td>
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">No roles defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
