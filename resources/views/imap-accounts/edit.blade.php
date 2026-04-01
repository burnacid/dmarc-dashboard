<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Configuration</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Edit IMAP account</h1>
                <p class="mt-2 text-sm text-slate-400">Update polling behavior or credentials for <span class="font-medium text-white">{{ $account->name }}</span>.</p>
            </div>
        </div>
    </x-slot>

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 lg:p-8">
        <form method="POST" action="{{ route('imap-accounts.update', $account) }}">
            @csrf
            @method('PUT')
            @include('imap-accounts._form', ['account' => $account])
        </form>
    </div>
</x-app-layout>

