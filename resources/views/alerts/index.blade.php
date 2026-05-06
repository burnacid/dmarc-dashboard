<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-300">Monitoring</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Alerts</h1>
                <p class="mt-2 text-sm text-slate-400">Create multiple SPF or DKIM spike rules and assign reusable notification channels.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('alerts.channels') }}" class="self-start rounded-2xl border border-sky-400/30 bg-sky-400/10 px-4 py-2 text-sm font-medium text-sky-100 transition hover:bg-sky-400/20 lg:self-auto">
                    Notification channels
                </a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
            {{ str_replace('-', ' ', session('status')) }}
        </div>
    @endif

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <details>
            <summary class="inline-flex cursor-pointer items-center rounded-2xl border border-sky-400/30 bg-sky-400/10 px-4 py-2 text-sm font-medium text-sky-100 transition hover:bg-sky-400/20">
                Create alert rule
            </summary>

            <form method="POST" action="{{ route('alerts.rules.store') }}" class="mt-4 space-y-4">
                {{ csrf_field() }}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="create_name" :value="__('Rule name')" />
                    <x-text-input id="create_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="SPF spike - all domains" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="create_metric" :value="__('Metric')" />
                    <select id="create_metric" name="metric" class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                        <option value="spf_fail_rate_spike" {{ old('metric') === 'spf_fail_rate_spike' ? 'selected' : '' }}>SPF fail rate spike</option>
                        <option value="dkim_fail_rate_spike" {{ old('metric') === 'dkim_fail_rate_spike' ? 'selected' : '' }}>DKIM fail rate spike</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('metric')" />
                </div>
                <div>
                    <x-input-label for="create_domain" :value="__('Domain scope (optional)')" />
                    <x-text-input id="create_domain" name="domain" type="text" class="mt-1 block w-full" :value="old('domain')" placeholder="Leave empty for all domains" />
                    <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-slate-900 text-sky-400" @checked(old('is_active', true))>
                        <span>Enabled</span>
                    </label>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="create_threshold_multiplier" :value="__('Spike multiplier')" />
                    <x-text-input id="create_threshold_multiplier" name="threshold_multiplier" type="number" step="0.01" min="1" max="20" class="mt-1 block w-full" :value="old('threshold_multiplier', 2.0)" />
                </div>
                <div>
                    <x-input-label for="create_min_absolute_increase" :value="__('Min increase (pp)')" />
                    <x-text-input id="create_min_absolute_increase" name="min_absolute_increase" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_absolute_increase', 8.0)" />
                </div>
                <div>
                    <x-input-label for="create_min_messages" :value="__('Min messages')" />
                    <x-text-input id="create_min_messages" name="min_messages" type="number" min="1" max="10000000" class="mt-1 block w-full" :value="old('min_messages', 200)" />
                </div>
                <div>
                    <x-input-label for="create_window_minutes" :value="__('Window minutes')" />
                    <x-text-input id="create_window_minutes" name="window_minutes" type="number" min="60" max="10080" class="mt-1 block w-full" :value="old('window_minutes', 1440)" />
                </div>
                <div>
                    <x-input-label for="create_baseline_days" :value="__('Baseline days')" />
                    <x-text-input id="create_baseline_days" name="baseline_days" type="number" min="1" max="90" class="mt-1 block w-full" :value="old('baseline_days', 14)" />
                </div>
                <div>
                    <x-input-label for="create_cooldown_minutes" :value="__('Cooldown minutes')" />
                    <x-text-input id="create_cooldown_minutes" name="cooldown_minutes" type="number" min="15" max="10080" class="mt-1 block w-full" :value="old('cooldown_minutes', 720)" />
                </div>
            </div>

            <div>
                <x-input-label for="create_channel_ids" :value="__('Notification channels')" />
                <select id="create_channel_ids" name="channel_ids[]" multiple class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->id }}">{{ $channel->name }} ({{ strtoupper($channel->type) }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl/Cmd to select multiple channels.</p>
                <x-input-error class="mt-2" :messages="$errors->get('channel_ids')" />
            </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Create alert rule') }}</x-primary-button>
                </div>
            </form>
        </details>
    </div>

    <div class="mt-8 space-y-4">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Recent activity</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Alert history</h2>
            <p class="mt-2 text-sm text-slate-400">See when each rule fired and send a test notification without waiting for the next scheduled evaluation.</p>
        </div>

        @forelse ($rules as $rule)
            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
                <details>
                    <summary class="cursor-pointer list-none">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ $rule->name }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ $rule->metric === 'spf_fail_rate_spike' ? 'SPF fail rate spike' : 'DKIM fail rate spike' }} · {{ $rule->domain ?: 'All domains' }}</p>
                            </div>
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-200">Expand</span>
                        </div>
                    </summary>

                <form method="POST" action="{{ route('alerts.rules.update', $rule) }}" class="mt-4 space-y-4">
                    {{ csrf_field() }}
                    {{ method_field('patch') }}

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label :value="__('Rule name')" />
                            <x-text-input name="name" type="text" class="mt-1 block w-full" :value="$rule->name" />
                        </div>
                        <div>
                            <x-input-label :value="__('Metric')" />
                            <select name="metric" class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                                <option value="spf_fail_rate_spike" {{ $rule->metric === 'spf_fail_rate_spike' ? 'selected' : '' }}>SPF fail rate spike</option>
                                <option value="dkim_fail_rate_spike" {{ $rule->metric === 'dkim_fail_rate_spike' ? 'selected' : '' }}>DKIM fail rate spike</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label :value="__('Domain scope (optional)')" />
                            <x-text-input name="domain" type="text" class="mt-1 block w-full" :value="$rule->domain" />
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-slate-900 text-sky-400" @checked($rule->is_active)>
                                <span>Enabled</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-text-input name="threshold_multiplier" type="number" step="0.01" min="1" max="20" class="mt-1 block w-full" :value="$rule->threshold_multiplier" />
                        <x-text-input name="min_absolute_increase" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="$rule->min_absolute_increase" />
                        <x-text-input name="min_messages" type="number" min="1" max="10000000" class="mt-1 block w-full" :value="$rule->min_messages" />
                        <x-text-input name="window_minutes" type="number" min="60" max="10080" class="mt-1 block w-full" :value="$rule->window_minutes" />
                        <x-text-input name="baseline_days" type="number" min="1" max="90" class="mt-1 block w-full" :value="$rule->baseline_days" />
                        <x-text-input name="cooldown_minutes" type="number" min="15" max="10080" class="mt-1 block w-full" :value="$rule->cooldown_minutes" />
                    </div>

                    <div>
                        <x-input-label :value="__('Notification channels')" />
                        <select name="channel_ids[]" multiple class="mt-1 block w-full rounded-2xl border-white/10 bg-slate-900 text-slate-100">
                            @php($selectedIds = $rule->notificationChannels->pluck('id')->all())
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}" {{ in_array($channel->id, $selectedIds, true) ? 'selected' : '' }}>{{ $channel->name }} ({{ strtoupper($channel->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save rule') }}</x-primary-button>
                    </div>
                </form>

                <div class="mt-4 flex gap-2">
                    <form method="POST" action="{{ route('alerts.test-fire', $rule) }}">
                        {{ csrf_field() }}
                        <button type="submit" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">Test fire</button>
                    </form>
                    <form method="POST" action="{{ route('alerts.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this alert rule?');">
                        {{ csrf_field() }}
                        {{ method_field('delete') }}
                        <button type="submit" class="rounded-xl border border-rose-400/30 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20">Delete</button>
                    </form>
                </div>

                @if ($rule->events->isNotEmpty())
                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Recent events</p>
                        <div class="space-y-2">
                            @foreach ($rule->events as $event)
                                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm">
                                    <span class="text-slate-300">{{ $event->triggered_at->format('Y-m-d H:i') }} UTC</span>
                                    <span class="text-slate-400">Current {{ number_format($event->current_fail_rate, 2) }}% · Baseline {{ number_format($event->baseline_fail_rate, 2) }}% · +{{ number_format($event->context['absolute_increase'] ?? 0, 2) }} pp</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                </details>
            </div>
        @empty
            <div class="rounded-3xl border border-white/10 bg-slate-900/40 p-12 text-center">
                <p class="text-slate-400">No alert rules configured yet.</p>
            </div>
        @endforelse

        <div class="mt-4">
            {{ $rules->links() }}
        </div>
    </div>
</x-app-layout>

