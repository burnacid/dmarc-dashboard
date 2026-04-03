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
        ];
    }
}

