<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Users</h1>
                <p class="mt-2 text-sm text-slate-400">Manage accounts, roles, and login access.</p>
            </div>

            <a href="{{ route('admin.users.create') }}" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                Add user
            </a>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 lg:grid-cols-4">
                <div class="space-y-2 lg:col-span-2">
                    <label for="q" class="text-sm font-medium text-slate-200">Search</label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Name or email" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="role" class="text-sm font-medium text-slate-200">Role</label>
                    <select id="role" name="role" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected($filters['role'] === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="is_active" class="text-sm font-medium text-slate-200">Status</label>
                    <select id="is_active" name="is_active" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">Any status</option>
                        <option value="1" @selected($filters['is_active'] === '1')>Active</option>
                        <option value="0" @selected($filters['is_active'] === '0')>Disabled</option>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3 lg:col-span-4">
                    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Apply filters
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="overflow-x-auto rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Email</th>
                            <th class="px-4 py-3 font-medium">Roles</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 align-top font-medium text-white">{{ $user->name }}</td>
                                <td class="px-4 py-3 align-top text-slate-300">{{ $user->email }}</td>
                                <td class="px-4 py-3 align-top">
                                    @forelse ($user->roles as $role)
                                        <span class="mr-1 mb-1 inline-block rounded-full bg-violet-400/15 px-2.5 py-1 text-xs font-semibold text-violet-200">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-slate-500">No roles</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-400/15 text-emerald-200' : 'bg-slate-400/10 text-slate-300' }}">
                                        {{ $user->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">No users match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
