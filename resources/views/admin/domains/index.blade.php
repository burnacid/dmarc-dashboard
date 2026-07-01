<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Domains</h1>
            <p class="mt-2 text-sm text-slate-400">Domains register automatically from imported DMARC reports. Assign each one to an organization to enable filtering.</p>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <form method="GET" action="{{ route('admin.domains.index') }}" class="grid gap-4 lg:grid-cols-4">
                <div class="space-y-2 lg:col-span-2">
                    <label for="q" class="text-sm font-medium text-slate-200">Search</label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="example.com" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>

                <div class="space-y-2">
                    <label for="organization_id" class="text-sm font-medium text-slate-200">Organization</label>
                    <select id="organization_id" name="organization_id" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All domains</option>
                        <option value="unassigned" @selected($filters['organization_id'] === 'unassigned')>Unassigned</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected($filters['organization_id'] === (string) $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                        Apply filters
                    </button>
                    <a href="{{ route('admin.domains.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <h2 class="text-sm font-semibold text-white">Add a domain manually</h2>
            <p class="mt-1 text-sm text-slate-400">Pre-register a domain before its first report arrives.</p>
            <form method="POST" action="{{ route('admin.domains.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium text-slate-200">Domain name</label>
                    <input id="name" name="name" type="text" placeholder="example.com" required class="w-64 rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
                </div>
                <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                    Add domain
                </button>
            </form>
            @error('name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </section>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="overflow-x-auto rounded-2xl border border-white/10">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Domain</th>
                            <th class="px-4 py-3 font-medium">Organization</th>
                            <th class="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/30 text-slate-200">
                        @forelse ($domains as $domain)
                            <tr>
                                <td class="px-4 py-3 align-top font-medium text-white">{{ $domain->name }}</td>
                                <td class="px-4 py-3 align-top">
                                    <form method="POST" action="{{ route('admin.domains.update', $domain) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="organization_id" onchange="this.form.submit()" class="rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
                                            <option value="">Unassigned</option>
                                            @foreach ($organizations as $organization)
                                                <option value="{{ $organization->id }}" @selected($domain->organization_id === $organization->id)>{{ $organization->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <form method="POST" action="{{ route('admin.domains.destroy', $domain) }}" onsubmit="return confirm('Remove this domain from the registry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-300 hover:text-rose-200">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400">No domains match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($domains->hasPages())
                <div class="mt-6">
                    {{ $domains->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
