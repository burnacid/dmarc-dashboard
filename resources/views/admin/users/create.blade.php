<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-violet-300">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Add user</h1>
            <p class="mt-2 text-sm text-slate-400">Create an account and assign initial roles.</p>
        </div>
    </x-slot>

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6 lg:p-8">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form', ['targetUser' => $targetUser, 'roles' => $roles, 'selectedRoleNames' => $selectedRoleNames])
        </form>
    </div>
</x-app-layout>
