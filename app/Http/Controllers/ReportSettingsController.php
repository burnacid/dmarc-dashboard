<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportSettingsUpdateRequest;
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

        return to_route('profile.edit')->with('status', 'report-settings-updated');
    }
}

