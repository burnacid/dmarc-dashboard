<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportSettingsUpdateRequest;
use App\Models\DmarcAlertRule;
use Illuminate\Http\RedirectResponse;

class ReportSettingsController extends Controller
{
    public function update(ReportSettingsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $presets = array_values(array_unique(array_map(
            'strval',
            (array) ($request->validated('dashboard_range_presets') ?? [])
        )));

        $user->forceFill([
            'report_retention_days' => $request->filled('report_retention_days')
                ? (int) $request->integer('report_retention_days')
                : null,
            'dashboard_range_presets' => $presets,
        ])->save();

        $rule = $user->dmarcAlertRules()
            ->where('metric', 'spf_fail_rate_spike')
            ->latest('id')
            ->first();

        $enabled = $request->boolean('alerts_spf_spike_enabled');

        if ($rule === null) {
            $rule = new DmarcAlertRule([
                'user_id' => $user->id,
                'metric' => 'spf_fail_rate_spike',
                'name' => 'SPF failure spike alert',
            ]);
        }

        $rule->forceFill([
            'domain' => $request->filled('alerts_spf_spike_domain')
                ? strtolower(trim((string) $request->string('alerts_spf_spike_domain')->toString()))
                : null,
            'threshold_multiplier' => $request->filled('alerts_spf_spike_threshold_multiplier')
                ? (float) $request->input('alerts_spf_spike_threshold_multiplier')
                : 2.0,
            'min_absolute_increase' => $request->filled('alerts_spf_spike_min_absolute_increase')
                ? (float) $request->input('alerts_spf_spike_min_absolute_increase')
                : 8.0,
            'min_messages' => $request->filled('alerts_spf_spike_min_messages')
                ? (int) $request->integer('alerts_spf_spike_min_messages')
                : 200,
            'window_minutes' => $request->filled('alerts_spf_spike_window_minutes')
                ? (int) $request->integer('alerts_spf_spike_window_minutes')
                : 1440,
            'baseline_days' => $request->filled('alerts_spf_spike_baseline_days')
                ? (int) $request->integer('alerts_spf_spike_baseline_days')
                : 14,
            'cooldown_minutes' => $request->filled('alerts_spf_spike_cooldown_minutes')
                ? (int) $request->integer('alerts_spf_spike_cooldown_minutes')
                : 720,
            'notification_email' => $request->filled('alerts_spf_spike_notification_email')
                ? trim((string) $request->string('alerts_spf_spike_notification_email')->toString())
                : null,
            'is_active' => $enabled,
        ]);

        $rule->save();

        return to_route('profile.edit')->with('status', 'report-settings-updated');
    }
}
