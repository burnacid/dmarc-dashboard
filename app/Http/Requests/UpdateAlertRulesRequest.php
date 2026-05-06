<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = (int) $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'metric' => ['required', 'string', Rule::in(['spf_fail_rate_spike', 'dkim_fail_rate_spike'])],
            'domain' => ['nullable', 'string', 'max:255'],
            'threshold_multiplier' => ['required', 'numeric', 'min:1', 'max:20'],
            'min_absolute_increase' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_messages' => ['required', 'integer', 'min:1', 'max:10000000'],
            'window_minutes' => ['required', 'integer', 'min:60', 'max:10080'],
            'baseline_days' => ['required', 'integer', 'min:1', 'max:90'],
            'cooldown_minutes' => ['required', 'integer', 'min:15', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
            'channel_ids' => ['nullable', 'array'],
            'channel_ids.*' => [
                'integer',
                Rule::exists('dmarc_notification_channels', 'id')->where('user_id', $userId),
            ],
        ];
    }
}

