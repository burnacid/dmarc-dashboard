@php
    $allowedRangePresets = \App\Models\User::allowedRangePresets();
    $selectedRangePresets = $user->normalizedRangePresets();
    $spfSpikeRule = $user->dmarcAlertRules()->where('metric', 'spf_fail_rate_spike')->latest('id')->first();
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-white">{{ __('Report settings') }}</h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __('Control automatic cleanup retention and which quick time buttons appear on your dashboard.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.report-settings.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="report_retention_days" :value="__('Retention in days (leave empty to keep all reports)')" />
            <x-text-input
                id="report_retention_days"
                name="report_retention_days"
                type="number"
                min="1"
                max="3650"
                class="mt-1 block w-full"
                :value="old('report_retention_days', $user->report_retention_days)"
                placeholder="{{ __('e.g. 180') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('report_retention_days')" />
            <p class="mt-2 text-xs text-slate-500">{{ __('A scheduled cleanup removes reports older than this window based on report end date (or import date when missing).') }}</p>
        </div>

        <div>
            <x-input-label :value="__('Dashboard quick range buttons')" />
            <p class="mt-1 text-xs text-slate-500">{{ __('Select the preset buttons you want available. If none are selected, defaults are restored automatically.') }}</p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($allowedRangePresets as $rangeValue => $rangeLabel)
                    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-200">
                        <input
                            type="checkbox"
                            name="dashboard_range_presets[]"
                            value="{{ $rangeValue }}"
                            class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400"
                            @checked(in_array($rangeValue, old('dashboard_range_presets', $selectedRangePresets), true))
                        >
                        <span>{{ $rangeLabel }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('dashboard_range_presets')" />
            <x-input-error class="mt-2" :messages="$errors->get('dashboard_range_presets.*')" />
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <x-input-label :value="__('SPF failure spike alerts')" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Notify me when SPF fail rate jumps compared to recent baseline traffic.') }}</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                    <input
                        type="checkbox"
                        name="alerts_spf_spike_enabled"
                        value="1"
                        class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400"
                        @checked(old('alerts_spf_spike_enabled', $spfSpikeRule?->is_active))
                    >
                    <span>{{ __('Enabled') }}</span>
                </label>
            </div>

            <div>
                <x-input-label for="alerts_spf_spike_domain" :value="__('Domain scope (optional)')" />
                <x-text-input
                    id="alerts_spf_spike_domain"
                    name="alerts_spf_spike_domain"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('alerts_spf_spike_domain', $spfSpikeRule?->domain)"
                    placeholder="{{ __('Leave empty for all domains') }}"
                />
                <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_domain')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="alerts_spf_spike_threshold_multiplier" :value="__('Spike multiplier')" />
                    <x-text-input id="alerts_spf_spike_threshold_multiplier" name="alerts_spf_spike_threshold_multiplier" type="number" step="0.01" min="1" max="20" class="mt-1 block w-full" :value="old('alerts_spf_spike_threshold_multiplier', $spfSpikeRule?->threshold_multiplier ?? 2.0)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_threshold_multiplier')" />
                </div>
                <div>
                    <x-input-label for="alerts_spf_spike_min_absolute_increase" :value="__('Min increase (percentage points)')" />
                    <x-text-input id="alerts_spf_spike_min_absolute_increase" name="alerts_spf_spike_min_absolute_increase" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('alerts_spf_spike_min_absolute_increase', $spfSpikeRule?->min_absolute_increase ?? 8.0)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_min_absolute_increase')" />
                </div>
                <div>
                    <x-input-label for="alerts_spf_spike_min_messages" :value="__('Min messages in window')" />
                    <x-text-input id="alerts_spf_spike_min_messages" name="alerts_spf_spike_min_messages" type="number" min="1" max="10000000" class="mt-1 block w-full" :value="old('alerts_spf_spike_min_messages', $spfSpikeRule?->min_messages ?? 200)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_min_messages')" />
                </div>
                <div>
                    <x-input-label for="alerts_spf_spike_window_minutes" :value="__('Window minutes')" />
                    <x-text-input id="alerts_spf_spike_window_minutes" name="alerts_spf_spike_window_minutes" type="number" min="60" max="10080" class="mt-1 block w-full" :value="old('alerts_spf_spike_window_minutes', $spfSpikeRule?->window_minutes ?? 1440)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_window_minutes')" />
                </div>
                <div>
                    <x-input-label for="alerts_spf_spike_baseline_days" :value="__('Baseline days')" />
                    <x-text-input id="alerts_spf_spike_baseline_days" name="alerts_spf_spike_baseline_days" type="number" min="1" max="90" class="mt-1 block w-full" :value="old('alerts_spf_spike_baseline_days', $spfSpikeRule?->baseline_days ?? 14)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_baseline_days')" />
                </div>
                <div>
                    <x-input-label for="alerts_spf_spike_cooldown_minutes" :value="__('Cooldown minutes')" />
                    <x-text-input id="alerts_spf_spike_cooldown_minutes" name="alerts_spf_spike_cooldown_minutes" type="number" min="15" max="10080" class="mt-1 block w-full" :value="old('alerts_spf_spike_cooldown_minutes', $spfSpikeRule?->cooldown_minutes ?? 720)" />
                    <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_cooldown_minutes')" />
                </div>
            </div>

            <div>
                <x-input-label for="alerts_spf_spike_notification_email" :value="__('Notification email (optional override)')" />
                <x-text-input
                    id="alerts_spf_spike_notification_email"
                    name="alerts_spf_spike_notification_email"
                    type="email"
                    class="mt-1 block w-full"
                    :value="old('alerts_spf_spike_notification_email', $spfSpikeRule?->notification_email)"
                    placeholder="{{ __('Defaults to your profile email') }}"
                />
                <x-input-error class="mt-2" :messages="$errors->get('alerts_spf_spike_notification_email')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save settings') }}</x-primary-button>

            @if (session('status') === 'report-settings-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

