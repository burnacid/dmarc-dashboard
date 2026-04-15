<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\AuthDiagnostics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        abort_if(! config('app.totp_enabled', true), 404);

        $user = $this->pendingUser($request);

        if (! $user) {
            $request->session()->forget('auth.two-factor');

            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(! config('app.totp_enabled', true), 404);

        AuthDiagnostics::log('two_factor.challenge.store.start', $request);

        $request->merge([
            'code' => $this->resolveOtpCode($request),
        ]);

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $state = $request->session()->get('auth.two-factor');
        $user = $this->pendingUser($request);

        AuthDiagnostics::log('two_factor.challenge.state', $request, [
            'state_present' => (bool) $state,
            'state_user_id' => is_array($state) ? ($state['user_id'] ?? null) : null,
            'remember_from_state' => (bool) (is_array($state) ? ($state['remember'] ?? false) : false),
            'pending_user_id' => $user?->getAuthIdentifier(),
        ]);

        if (! $state || ! $user) {
            $request->session()->forget('auth.two-factor');

            AuthDiagnostics::log('two_factor.challenge.redirect_login', $request, [
                'reason' => 'missing_state_or_user',
            ], 'warning');

            return redirect()->route('login');
        }

        if (! $user->validateTwoFactorCode($request->string('code')->toString())) {
            AuthDiagnostics::log('two_factor.challenge.invalid_code', $request, [
                'user_id' => $user->getAuthIdentifier(),
            ], 'warning');

            throw ValidationException::withMessages([
                'code' => __('The authentication code is invalid. You may also use a recovery code.'),
            ]);
        }

        $request->session()->forget('auth.two-factor');

        Auth::login($user, (bool) ($state['remember'] ?? false));
        $request->session()->regenerate();

        AuthDiagnostics::log('two_factor.challenge.success', $request, [
            'user_id' => $user->getAuthIdentifier(),
            'remember_effective' => (bool) ($state['remember'] ?? false),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    protected function pendingUser(Request $request): ?User
    {
        if (! config('app.totp_enabled', true)) {
            return null;
        }

        $state = $request->session()->get('auth.two-factor');

        if (! is_array($state) || empty($state['user_id'])) {
            return null;
        }

        $user = User::query()->find($state['user_id']);

        if (! $user?->hasTwoFactorEnabled()) {
            return null;
        }

        return $user;
    }

    private function resolveOtpCode(Request $request): string
    {
        foreach (['code', 'otp', 'totp', 'verification_code', 'mfa_code', 'two_factor_code'] as $key) {
            $value = trim((string) $request->input($key, ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

