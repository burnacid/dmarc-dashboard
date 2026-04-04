<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laragear\WebAuthn\Models\WebAuthnCredential;

class SecurityController extends Controller
{
    public function storeTwoFactor(Request $request): RedirectResponse
    {
        abort_if(! config('app.totp_enabled', true), 404);

        $request->validateWithBag('enableTwoFactor', [
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->createTwoFactorAuth();

        return back()->with('two-factor-status', __('Scan the QR code with your authenticator app, then confirm it below.'));
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        abort_if(! config('app.totp_enabled', true), 404);

        $request->merge([
            'code' => $this->resolveOtpCode($request),
        ]);

        $request->validateWithBag('confirmTwoFactor', [
            'code' => ['required', 'string'],
        ]);

        if (! $request->user()->confirmTwoFactorAuth($request->string('code')->toString())) {
            return back()->withErrors([
                'code' => __('The authentication code is invalid.'),
            ], 'confirmTwoFactor');
        }

        return back()->with('two-factor-status', __('Two-factor authentication is now enabled for your account.'));
    }

    public function destroyTwoFactor(Request $request): RedirectResponse
    {
        abort_if(! config('app.totp_enabled', true), 404);

        $request->validateWithBag('disableTwoFactor', [
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->disableTwoFactorAuth();
        $request->session()->forget('auth.two-factor');

        return back()->with('two-factor-status', __('Two-factor authentication has been disabled.'));
    }

    public function storeRecoveryCodes(Request $request): RedirectResponse
    {
        abort_if(! config('app.totp_enabled', true), 404);

        if (! $request->user()->hasTwoFactorEnabled()) {
            return back()->with('two-factor-status', __('Enable two-factor authentication before generating recovery codes.'));
        }

        $request->user()->generateRecoveryCodes();

        return back()->with('two-factor-status', __('A fresh set of recovery codes has been generated. Store them somewhere safe.'));
    }

    public function destroyPasskey(Request $request, WebAuthnCredential $credential): RedirectResponse
    {
        abort_if(! config('app.passkeys_enabled', true), 404);

        $request->validateWithBag('deletePasskey', [
            'password' => ['required', 'current_password'],
        ]);

        $ownedCredential = $request->user()
            ->webAuthnCredentials()
            ->whereKey($credential->getKey())
            ->firstOrFail();

        $ownedCredential->delete();

        return back()->with('passkey-status', __('Passkey removed successfully.'));
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

