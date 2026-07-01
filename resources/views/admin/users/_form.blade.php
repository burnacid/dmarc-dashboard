@php
    $isEdit = $targetUser->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="text-sm font-medium text-slate-200">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $targetUser->name) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('name') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="email" class="text-sm font-medium text-slate-200">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $targetUser->email) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('email') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="password" class="text-sm font-medium text-slate-200">Password{{ $isEdit ? ' (leave blank to keep current)' : '' }}</label>
        <input id="password" name="password" type="password" {{ $isEdit ? '' : 'required' }} class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('password') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $targetUser->is_active ?? true)) class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400">
        <div>
            <p class="text-sm font-medium text-white">Account is active</p>
            <p class="text-xs text-slate-400">Disabled accounts cannot log in and are logged out immediately.</p>
        </div>
    </label>

    <div class="space-y-2 lg:col-span-2">
        <p class="text-sm font-medium text-slate-200">Roles</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse ($roles as $role)
                <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', $selectedRoleNames), true)) class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400">
                    <span class="text-sm text-white">{{ $role->name }}</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No roles defined yet.</p>
            @endforelse
        </div>
        @error('roles') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-8 flex flex-wrap items-center gap-3">
    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
        {{ $isEdit ? 'Save changes' : 'Create user' }}
    </button>
    <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
        Cancel
    </a>
</div>
