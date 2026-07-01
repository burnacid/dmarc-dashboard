<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Add organization</h1>
        </div>
    </x-slot>

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 lg:p-8">
        <form method="POST" action="{{ route('admin.organizations.store') }}">
            @csrf
            @include('admin.organizations._form', ['organization' => $organization])
        </form>
    </div>
</x-app-layout>
