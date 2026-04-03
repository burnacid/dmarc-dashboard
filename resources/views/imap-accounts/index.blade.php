<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Configuration</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">IMAP accounts</h1>
                <p class="mt-2 text-sm text-slate-400">Connect one or more inboxes that receive DMARC aggregate reports.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('dashboard.poll-now') }}">
                    @csrf
                    <button type="submit" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                        Poll active accounts
                    </button>
                </form>
                <a href="{{ route('imap-accounts.create') }}" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                    Add account
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($accounts as $account)
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-semibold text-white">{{ $account->name }}</h2>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-400/15 text-emerald-200' : 'bg-slate-400/10 text-slate-300' }}">
                                {{ $account->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">{{ $account->username }} @ {{ $account->host }}:{{ $account->port }} · {{ strtoupper($account->encryption) }}</p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('imap-accounts.edit', $account) }}" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                            Edit
                        </a>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Folder</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->folder }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Search</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->search_criteria }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Processed to</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->processed_folder ?: 'Keep in source folder' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Errors to</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->error_folder ?: 'Keep in source folder' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Reports</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->reports_count }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <dt class="text-xs uppercase tracking-[0.2em] text-slate-500">Last poll</dt>
                        <dd class="mt-2 text-sm font-medium text-white">{{ $account->last_polled_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('dashboard.poll-now') }}">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        <button type="submit" class="rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                            Poll this account
                        </button>
                    </form>

                    <form method="POST" action="{{ route('imap-accounts.destroy', $account) }}" onsubmit="return confirm('Delete this IMAP account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-white/10 bg-white/5 p-8 lg:col-span-2">
                <h2 class="text-xl font-semibold text-white">No IMAP accounts configured</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-400">Create your first connection to start importing DMARC aggregate reports from provider mailboxes such as Microsoft 365, Google Workspace, or standard IMAP servers.</p>
                <a href="{{ route('imap-accounts.create') }}" class="mt-6 inline-flex rounded-2xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                    Add your first account
                </a>
            </div>
        @endforelse
    </div>
</x-app-layout>

