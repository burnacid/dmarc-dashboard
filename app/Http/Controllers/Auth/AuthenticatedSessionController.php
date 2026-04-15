<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\AuthDiagnostics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        AuthDiagnostics::log('password.store.after_authenticate', $request, [
            'user_id' => Auth::id(),
            'remember_requested' => $request->boolean('remember'),
        ]);

        $user = $request->user();

        if (config('app.totp_enabled', true) && $user?->hasTwoFactorEnabled()) {
            $request->session()->put('auth.two-factor', [
                'user_id' => $user->getAuthIdentifier(),
                'remember' => $request->boolean('remember'),
            ]);

            AuthDiagnostics::log('password.store.redirect_two_factor', $request, [
                'user_id' => $user->getAuthIdentifier(),
                'remember_requested' => $request->boolean('remember'),
            ]);

            Auth::guard('web')->logout();

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        AuthDiagnostics::log('password.store.redirect_dashboard', $request, [
            'user_id' => $user?->getAuthIdentifier(),
            'remember_requested' => $request->boolean('remember'),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('auth.two-factor');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
