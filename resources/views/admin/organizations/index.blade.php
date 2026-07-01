<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Organizations</h1>
                <p class="mt-2 text-sm text-slate-400">Group domains for filtering across the dashboard and reports.</p>
            </div>

            <a href="{{ route('admin.organizations.create') }}" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                Add organization
            </a>
        </div>
    </x-slot>

    <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <div class="overflow-x-auto rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-left text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Domains</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                    @forelse ($organizations as $organization)
                        <tr>
                            <td class="px-4 py-3 align-top font-medium text-white">{{ $organization->name }}</td>
                            <td class="px-4 py-3 align-top text-slate-300">{{ $organization->domains_count }}</td>
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-400">No organizations defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($organizations->hasPages())
            <div class="mt-6">
                {{ $organizations->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
