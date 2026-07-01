<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Edit organization</h1>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 lg:p-8">
            <form method="POST" action="{{ route('admin.organizations.update', $organization) }}">
                @csrf
                @method('PATCH')
                @include('admin.organizations._form', ['organization' => $organization])
            </form>
        </div>

        <section class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-white">Domains in this organization</h2>
                <a href="{{ route('admin.domains.index', ['organization_id' => $organization->id]) }}" class="text-sm font-medium text-sky-300 hover:text-sky-200">Manage domains</a>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($organization->domains as $domain)
                    <span class="rounded-full bg-white/5 px-3 py-1 text-xs text-slate-200">{{ $domain->name }}</span>
                @empty
                    <p class="text-sm text-slate-500">No domains assigned yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-rose-400/20 bg-rose-400/5 p-6">
            <h2 class="text-sm font-semibold text-rose-300">Delete organization</h2>
            <p class="mt-1 text-sm text-slate-400">Domains stay in the registry but become unassigned.</p>
            <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" class="mt-4" onsubmit="return confirm('Delete this organization?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-400/20">
                    Delete organization
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
