<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportSettingsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'dashboard_range_presets' => ['nullable', 'array'],
            'dashboard_range_presets.*' => ['string', 'in:'.implode(',', array_keys(User::allowedRangePresets()))],
            'alerts_spf_spike_enabled' => ['nullable', 'boolean'],
            'alerts_spf_spike_domain' => ['nullable', 'string', 'max:255'],
            'alerts_spf_spike_threshold_multiplier' => ['nullable', 'numeric', 'min:1', 'max:20'],
            'alerts_spf_spike_min_absolute_increase' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alerts_spf_spike_min_messages' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'alerts_spf_spike_window_minutes' => ['nullable', 'integer', 'min:60', 'max:10080'],
            'alerts_spf_spike_baseline_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'alerts_spf_spike_cooldown_minutes' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'alerts_spf_spike_notification_email' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }
}
