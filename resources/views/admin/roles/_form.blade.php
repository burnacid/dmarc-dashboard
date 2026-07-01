@php
    $isEdit = $targetRole->exists;
    $isBuiltIn = $isEdit && in_array($targetRole->name, $builtInRoles ?? [], true);
@endphp

<div class="grid gap-6">
    <div class="space-y-2">
        <label for="name" class="text-sm font-medium text-slate-200">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $targetRole->name) }}" required {{ $isBuiltIn ? 'readonly' : '' }} class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0 {{ $isBuiltIn ? 'opacity-60' : '' }}">
        @if ($isBuiltIn)
            <p class="text-xs text-slate-500">Built-in role names cannot be changed.</p>
        @endif
        @error('name') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <p class="text-sm font-medium text-slate-200">Permissions</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse ($permissions as $permission)
                <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', $selectedPermissionNames), true)) class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400">
                    <span class="text-sm text-white">{{ $permission->name }}</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No permissions defined yet.</p>
            @endforelse
        </div>
        @error('permissions') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-8 flex flex-wrap items-center gap-3">
    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
        {{ $isEdit ? 'Save changes' : 'Create role' }}
    </button>
    <a href="{{ route('admin.roles.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
        Cancel
    </a>
</div>
