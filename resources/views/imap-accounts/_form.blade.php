@php
    $isEdit = $account->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-2 lg:col-span-2">
        <label for="name" class="text-sm font-medium text-slate-200">Display name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $account->name) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('name') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="host" class="text-sm font-medium text-slate-200">IMAP host</label>
        <input id="host" name="host" type="text" value="{{ old('host', $account->host) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('host') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="port" class="text-sm font-medium text-slate-200">Port</label>
        <input id="port" name="port" type="number" value="{{ old('port', $account->port ?? 993) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('port') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="encryption" class="text-sm font-medium text-slate-200">Encryption</label>
        <select id="encryption" name="encryption" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white focus:border-sky-400 focus:outline-none focus:ring-0">
            @foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'] as $value => $label)
                <option value="{{ $value }}" @selected(old('encryption', $account->encryption ?? 'ssl') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('encryption') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="username" class="text-sm font-medium text-slate-200">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username', $account->username) }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('username') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="password" class="text-sm font-medium text-slate-200">Password{{ $isEdit ? ' (leave blank to keep current)' : '' }}</label>
        <input id="password" name="password" type="password" {{ $isEdit ? '' : 'required' }} class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('password') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="folder" class="text-sm font-medium text-slate-200">Folder</label>
        <input id="folder" name="folder" type="text" value="{{ old('folder', $account->folder ?? 'INBOX') }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        @error('folder') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="processed_folder" class="text-sm font-medium text-slate-200">Processed message folder</label>
        <input id="processed_folder" name="processed_folder" type="text" value="{{ old('processed_folder', $account->processed_folder) }}" placeholder="DMARC/Processed" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        <p class="text-xs text-slate-500">Optional. After a message is successfully imported, it will be moved here to keep the inbox small.</p>
        @error('processed_folder') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="error_folder" class="text-sm font-medium text-slate-200">Import error folder</label>
        <input id="error_folder" name="error_folder" type="text" value="{{ old('error_folder', $account->error_folder) }}" placeholder="DMARC/Error" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        <p class="text-xs text-slate-500">Optional. Messages that fail to import will be moved here for manual review.</p>
        @error('error_folder') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="search_criteria" class="text-sm font-medium text-slate-200">Search criteria</label>
        <input id="search_criteria" name="search_criteria" type="text" value="{{ old('search_criteria', $account->search_criteria ?? 'UNSEEN') }}" required class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm uppercase text-white placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-0">
        <p class="text-xs text-slate-500">Examples: <code>UNSEEN</code>, <code>ALL</code>, <code>SEEN</code>.</p>
        @error('search_criteria') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-4 lg:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true)) class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400">
        <div>
            <p class="text-sm font-medium text-white">Enable this account for scheduled polling</p>
            <p class="text-xs text-slate-400">Disabled accounts stay visible but are skipped by the scheduled command.</p>
        </div>
    </label>
</div>

<div class="mt-8 flex flex-wrap items-center gap-3">
    <button type="submit" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
        {{ $isEdit ? 'Save changes' : 'Create account' }}
    </button>
    <a href="{{ route('imap-accounts.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
        Cancel
    </a>
</div>

