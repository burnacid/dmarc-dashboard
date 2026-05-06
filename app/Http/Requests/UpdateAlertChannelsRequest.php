<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertChannelsRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['email', 'ntfy'])],
            'is_active' => ['nullable', 'boolean'],

            'email_to' => ['nullable', 'email:rfc', 'max:255', 'required_if:type,email'],

            'ntfy_url' => ['nullable', 'url', 'max:500', 'required_if:type,ntfy'],
            'ntfy_token' => ['nullable', 'string', 'max:255'],
            'ntfy_ignore_certificate' => ['nullable', 'boolean'],
        ];
    }
}
