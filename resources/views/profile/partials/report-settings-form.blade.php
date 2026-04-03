@php
    $allowedRangePresets = \App\Models\User::allowedRangePresets();
    $selectedRangePresets = $user->normalizedRangePresets();
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

