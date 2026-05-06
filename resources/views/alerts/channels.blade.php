<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Delivery</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Notification channels</h1>
                <p class="mt-2 text-sm text-slate-400">Create reusable channels once, then attach them to any SPF or DKIM alert rule.</p>
            </div>
            <a href="{{ route('alerts.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                Alert rules
            </a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
            {{ str_replace('-', ' ', session('status')) }}
        </div>
    @endif

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <h2 class="text-xl font-semibold text-white">Create channel</h2>

        <form method="POST" action="{{ route('alerts.channels.store') }}" class="mt-4 space-y-4">
            {{ csrf_field() }}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="create_channel_name" :value="__('Channel name')" />
                    <x-text-input id="create_channel_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="Ops email" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="create_channel_type" :value="__('Type')" />
                    <select id="create_channel_type" name="type" class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                        <option value="email" {{ old('type', 'email') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="ntfy" {{ old('type') === 'ntfy' ? 'selected' : '' }}>ntfy</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="create_email_to" :value="__('Email recipient (for Email type)')" />
                    <x-text-input id="create_email_to" name="email_to" type="email" class="mt-1 block w-full" :value="old('email_to')" placeholder="alerts@example.com" />
                    <x-input-error class="mt-2" :messages="$errors->get('email_to')" />
                </div>

                <div>
                    <x-input-label for="create_ntfy_url" :value="__('ntfy topic URL (for ntfy type)')" />
                    <x-text-input id="create_ntfy_url" name="ntfy_url" type="url" class="mt-1 block w-full" :value="old('ntfy_url')" placeholder="https://ntfy.sh/my-topic" />
                    <x-input-error class="mt-2" :messages="$errors->get('ntfy_url')" />
                </div>

                <div>
                    <x-input-label for="create_ntfy_token" :value="__('ntfy token (optional)')" />
                    <x-text-input id="create_ntfy_token" name="ntfy_token" type="password" class="mt-1 block w-full" :value="old('ntfy_token')" autocomplete="off" />
                    <x-input-error class="mt-2" :messages="$errors->get('ntfy_token')" />
                </div>

                <div class="space-y-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-slate-900 text-sky-400" @checked(old('is_active', true))>
                        <span>Active</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-amber-200">
                        <input type="checkbox" name="ntfy_ignore_certificate" value="1" class="rounded border-white/20 bg-slate-900 text-amber-400" @checked(old('ntfy_ignore_certificate'))>
                        <span>Ignore ntfy certificate errors (insecure)</span>
                    </label>
                </div>
            </div>

            <div>
                <x-primary-button>{{ __('Create channel') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="mt-8 space-y-4">
        <h2 class="text-2xl font-semibold text-white">Existing channels</h2>

        @forelse ($channels as $channel)
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <form method="POST" action="{{ route('alerts.channels.update', $channel) }}" class="space-y-4">
                    {{ csrf_field() }}
                    {{ method_field('patch') }}

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label :value="__('Channel name')" />
                            <x-text-input name="name" type="text" class="mt-1 block w-full" :value="$channel->name" />
                        </div>
                        <div>
                            <x-input-label :value="__('Type')" />
                            <select name="type" class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                                <option value="email" {{ $channel->type === 'email' ? 'selected' : '' }}>Email</option>
                                <option value="ntfy" {{ $channel->type === 'ntfy' ? 'selected' : '' }}>ntfy</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label :value="__('Email recipient')" />
                            <x-text-input name="email_to" type="email" class="mt-1 block w-full" :value="$channel->email_to" />
                        </div>

                        <div>
                            <x-input-label :value="__('ntfy topic URL')" />
                            <x-text-input name="ntfy_url" type="url" class="mt-1 block w-full" :value="$channel->ntfy_url" />
                        </div>

                        <div>
                            <x-input-label :value="__('ntfy token (optional)')" />
                            <x-text-input name="ntfy_token" type="password" class="mt-1 block w-full" :value="$channel->ntfy_token" autocomplete="off" />
                        </div>

                        <div class="space-y-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-slate-900 text-sky-400" @checked($channel->is_active)>
                                <span>Active</span>
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm text-amber-200">
                                <input type="checkbox" name="ntfy_ignore_certificate" value="1" class="rounded border-white/20 bg-slate-900 text-amber-400" @checked($channel->ntfy_ignore_certificate)>
                                <span>Ignore ntfy certificate errors (insecure)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <x-primary-button>{{ __('Save channel') }}</x-primary-button>
                    </div>
                </form>

                <form method="POST" action="{{ route('alerts.channels.destroy', $channel) }}" class="mt-2" onsubmit="return confirm('Delete this channel?');">
                    {{ csrf_field() }}
                    {{ method_field('delete') }}
                    <button type="submit" class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20">Delete</button>
                </form>
            </div>
        @empty
            <div class="rounded-3xl border border-white/10 bg-slate-900/40 p-12 text-center">
                <p class="text-slate-400">No notification channels yet.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>

