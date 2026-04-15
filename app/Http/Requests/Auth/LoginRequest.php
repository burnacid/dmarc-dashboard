<?php

namespace App\Http\Requests\Auth;

use App\Support\Auth\AuthDiagnostics;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        AuthDiagnostics::log('password.attempt', $this, [
            'email_hash' => AuthDiagnostics::emailHash($this->input('email')),
        ]);

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            AuthDiagnostics::log('password.failed', $this, [
                'reason' => 'invalid_credentials',
                'email_hash' => AuthDiagnostics::emailHash($this->input('email')),
            ], 'warning');

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        AuthDiagnostics::log('password.success', $this, [
            'user_id' => Auth::id(),
            'remember_requested' => $this->boolean('remember'),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        AuthDiagnostics::log('password.lockout', $this, [
            'email_hash' => AuthDiagnostics::emailHash($this->input('email')),
            'retry_after_seconds' => RateLimiter::availableIn($this->throttleKey()),
        ], 'warning');

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
