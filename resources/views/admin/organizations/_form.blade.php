@php
    $isEdit = $organization->exists;
@endphp

<div class="space-y-2">
    <label for="name" class="text-sm font-medium text-slate-200">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $organization->name) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
    @error('name') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
</div>

<div class="mt-8 flex flex-wrap items-center gap-3">
    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
        {{ $isEdit ? 'Save changes' : 'Create organization' }}
    </button>
    <a href="{{ route('admin.organizations.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
        Cancel
    </a>
</div>
